<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;

final class UserId
{
    private int $value;

    private function __construct(int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('El id de usuario debe ser un entero positivo.');
        }
        $this->value = $value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    /** Acepta lo que venga de HTTP (string, null, etc.) y falla si no es válido. */
    public static function fromMixed($value): self
    {
        return new self((int) $value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
