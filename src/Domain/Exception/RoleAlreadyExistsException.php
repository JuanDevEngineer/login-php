<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class RoleAlreadyExistsException extends DomainException
{
    public static function withName(string $name): self
    {
        return new self(sprintf('Ya existe un rol llamado "%s".', $name));
    }
}
