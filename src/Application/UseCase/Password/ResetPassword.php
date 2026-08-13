<?php

declare(strict_types=1);

namespace App\Application\UseCase\Password;

use App\Domain\Port\PasswordHasher;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\PlainPassword;

final class ResetPassword
{
    private ValidateRecoveryToken $validator;
    private UserRepository $users;
    private PasswordHasher $hasher;

    public function __construct(
        ValidateRecoveryToken $validator,
        UserRepository $users,
        PasswordHasher $hasher
    ) {
        $this->validator = $validator;
        $this->users     = $users;
        $this->hasher    = $hasher;
    }

    /**
     * Revalidamos el token acá y no confiamos en un id enviado por el
     * formulario: si no, cualquiera podría cambiar la contraseña de otro
     * simplemente alterando el campo oculto.
     */
    public function execute(string $selector, string $verifier, string $rawPassword): void
    {
        $user     = $this->validator->execute($selector, $verifier);
        $password = PlainPassword::fromString($rawPassword);

        // changePassword() también borra el token: un solo uso.
        $user->changePassword($this->hasher->hash($password));

        $this->users->save($user);
    }
}
