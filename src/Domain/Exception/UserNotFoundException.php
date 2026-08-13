<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class UserNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('No existe el usuario con id %d.', $id));
    }

    public static function create(): self
    {
        return new self('El usuario no existe.');
    }
}
