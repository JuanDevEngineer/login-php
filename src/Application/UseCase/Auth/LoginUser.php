<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Dto\AuthenticatedUser;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Port\Clock;
use App\Domain\Port\LoginLog;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\SessionStorage;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\PlainPassword;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;

final class LoginUser
{
    public const SESSION_KEY = 'auth_user';

    /**
     * Hash bcrypt válido (coste 12) que no corresponde a ninguna contraseña
     * usable. Solo existe para consumir el mismo tiempo de CPU cuando el
     * usuario no existe.
     */
    private const DECOY_HASH = '$2y$12$C6UzMDM.H6dfI/f/IKcEe.4Vv6ELIrMWiw6XKrlBmXqzWCG7Ky5.O';

    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly SessionStorage $session,
        private readonly LoginLog $logins,
        private readonly Clock $clock,
    ) {
    }

    /**
     * @throws InvalidCredentialsException
     */
    public function execute(string $rawUsername, string $rawPassword): AuthenticatedUser
    {
        $username = Username::fromString($rawUsername);
        $password = PlainPassword::forVerification($rawPassword);

        $user = $this->users->findByUsername($username);

        if ($user === null) {
            // Verificamos igual contra un hash señuelo con el mismo coste. Sin
            // esto, un usuario inexistente respondería mucho más rápido que uno
            // real y se podría enumerar quién está registrado midiendo tiempos.
            // El hash debe tener formato bcrypt válido o password_verify()
            // retorna de inmediato y la defensa no sirve de nada.
            $this->hasher->verify($password, HashedPassword::fromHash(self::DECOY_HASH));

            throw InvalidCredentialsException::create();
        }

        if (!$this->hasher->verify($password, $user->password())) {
            throw InvalidCredentialsException::create();
        }

        if (!$user->isActive()) {
            throw new AccessDeniedException('La cuenta está desactivada.');
        }

        // Rehash transparente si el coste de bcrypt cambió.
        if ($this->hasher->needsRehash($user->password())) {
            $user->changePassword($this->hasher->hash($password));
            $this->users->save($user);
        }

        // Session fixation: rotamos el id antes de marcar la sesión como autenticada.
        $this->session->regenerate();

        $authenticated = AuthenticatedUser::fromEntity($user);
        $this->session->set(self::SESSION_KEY, $authenticated->toArray());

        $this->recordAccess($user->id());

        return $authenticated;
    }

    /**
     * Deja constancia del acceso. Va envuelto en try/catch a propósito: la
     * bitácora es una métrica, no parte del contrato de autenticación. Si la
     * tabla no existe todavía o la escritura falla, el usuario ya está
     * autenticado y no tiene por qué quedarse fuera; el problema va al log.
     */
    private function recordAccess(?UserId $id): void
    {
        if ($id === null) {
            return;
        }

        try {
            $this->logins->record($id, $this->clock->now());
        } catch (\Throwable $e) {
            error_log('[login] no se pudo registrar el acceso: ' . $e->getMessage());
        }
    }
}
