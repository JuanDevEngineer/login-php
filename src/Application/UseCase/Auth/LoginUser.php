<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Dto\AuthenticatedUser;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\SessionStorage;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\PlainPassword;
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

    private UserRepository $users;
    private PasswordHasher $hasher;
    private SessionStorage $session;

    public function __construct(UserRepository $users, PasswordHasher $hasher, SessionStorage $session)
    {
        $this->users   = $users;
        $this->hasher  = $hasher;
        $this->session = $session;
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
            throw new \App\Domain\Exception\AccessDeniedException('La cuenta está desactivada.');
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

        return $authenticated;
    }
}
