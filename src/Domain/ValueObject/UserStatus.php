<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Estado de la cuenta. En base de datos es un tinyint (0 / 1); acá le damos
 * nombre para que el código no razone con números mágicos.
 */
final class UserStatus
{
    private const ACTIVE   = 1;
    private const INACTIVE = 0;

    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value === self::ACTIVE ? self::ACTIVE : self::INACTIVE;
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function inactive(): self
    {
        return new self(self::INACTIVE);
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    /** Devuelve el estado opuesto (usado por el toggle del gestor). */
    public function toggled(): self
    {
        return $this->isActive() ? self::inactive() : self::active();
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function label(): string
    {
        return $this->isActive() ? 'activo' : 'inactivo';
    }
}
