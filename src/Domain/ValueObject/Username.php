<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;

final class Username
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 50;

    private string $value;

    private function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('El nombre de usuario es obligatorio.');
        }
        $length = mb_strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'El nombre de usuario debe tener entre %d y %d caracteres.',
                self::MIN_LENGTH,
                self::MAX_LENGTH
            ));
        }
        if (!preg_match('/^[\p{L}\p{N}._-]+$/u', $value)) {
            throw new InvalidArgumentException(
                'El nombre de usuario solo admite letras, números, punto, guion y guion bajo.'
            );
        }

        $this->value = $value;
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
