<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ProtectedRoleException extends DomainException
{
    public static function forRole(string $name): self
    {
        return new self(sprintf(
            'El rol "%s" es del sistema: no se puede renombrar ni eliminar porque '
            . 'la aplicación depende de él.',
            $name
        ));
    }
}
