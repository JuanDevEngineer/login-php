<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class UserAlreadyExistsException extends DomainException
{
    public static function withUsername(string $username): self
    {
        return new self(sprintf('El usuario "%s" ya está registrado.', $username));
    }

    public static function withEmail(string $email): self
    {
        return new self(sprintf('El correo "%s" ya está registrado.', $email));
    }
}
