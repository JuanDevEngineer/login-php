<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;

/**
 * Nombre de un rol.
 *
 * Desde que el admin puede crear roles, este valor deja de venir solo de un
 * seed controlado y pasa a ser entrada de usuario: por eso el formato está
 * acotado. Se normaliza a mayúsculas para que "ventas" y "VENTAS" no puedan
 * coexistir como roles distintos.
 */
final class RoleName
{
    public const ADMIN = 'ROL_ADMIN';
    public const USER  = 'ROL_USER';

    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 50;

    private string $value;

    private function __construct(string $value)
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            throw new InvalidArgumentException('El nombre del rol es obligatorio.');
        }

        $length = strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'El nombre del rol debe tener entre %d y %d caracteres.',
                self::MIN_LENGTH,
                self::MAX_LENGTH
            ));
        }

        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $value)) {
            throw new InvalidArgumentException(
                'El nombre del rol debe empezar por una letra y contener solo '
                . 'letras sin tilde, números y guion bajo. Por ejemplo: ROL_VENTAS.'
            );
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function user(): self
    {
        return new self(self::USER);
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
