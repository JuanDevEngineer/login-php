<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;

final class Email
{
    private const MAX_LENGTH = 200;

    private string $value;

    private function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('El correo es obligatorio.');
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('El correo es demasiado largo.');
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo no tiene un formato válido.');
        }

        $this->value = mb_strtolower($value);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
