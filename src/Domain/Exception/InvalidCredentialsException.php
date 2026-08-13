<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidCredentialsException extends DomainException
{
    public static function create(): self
    {
        // Mensaje deliberadamente genérico: no revelamos si falló el usuario
        // o la contraseña, para no permitir enumeración de cuentas.
        return new self('Usuario o contraseña incorrectos.');
    }
}
