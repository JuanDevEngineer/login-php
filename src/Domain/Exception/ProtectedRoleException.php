<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ProtectedRoleException extends DomainException
{
    /** Intento de editar los permisos de un rol que los tiene implícitos. */
    public static function permissionsAreImplicit(string $name): self
    {
        return new self(sprintf(
            'El rol "%s" tiene acceso total por definición: sus permisos no se '
            . 'administran desde la matriz.',
            $name
        ));
    }

    public static function forRole(string $name): self
    {
        return new self(sprintf(
            'El rol "%s" es del sistema: no se puede renombrar ni eliminar porque '
            . 'la aplicación depende de él.',
            $name
        ));
    }
}
