<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class RoleInUseException extends DomainException
{
    public static function withUsers(string $name, int $count): self
    {
        return new self(sprintf(
            'No se puede eliminar el rol "%s": %d %s asignado. Reasignalos antes de borrarlo.',
            $name,
            $count,
            $count === 1 ? 'usuario lo tiene' : 'usuarios lo tienen'
        ));
    }
}
