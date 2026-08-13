<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class RoleNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('No existe el rol con id %d.', $id));
    }
}
