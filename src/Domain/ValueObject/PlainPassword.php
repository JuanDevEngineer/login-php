<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;

/**
 * Contraseña en claro. Vive solo el tiempo necesario para hashearla o
 * verificarla contra un hash; nunca se persiste ni se serializa.
 */
final class PlainPassword
{
    public const MIN_LENGTH = 8;
    private const MAX_LENGTH = 4096; // tope defensivo contra DoS por hashing

    private string $value;

    private function __construct(string $value, bool $enforcePolicy)
    {
        if ($value === '') {
            throw new InvalidArgumentException('La contraseña es obligatoria.');
        }
        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('La contraseña es demasiado larga.');
        }
        if ($enforcePolicy && strlen($value) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'La contraseña debe tener al menos %d caracteres.',
                self::MIN_LENGTH
            ));
        }

        $this->value = $value;
    }

    /**
     * Contraseña nueva: se aplica la política de longitud mínima.
     * Usar en registro y en cambio de contraseña.
     */
    public static function fromString(string $value): self
    {
        return new self($value, true);
    }

    /**
     * Contraseña que solo se va a comparar contra un hash existente. No se
     * aplica la política porque una cuenta antigua puede tener una contraseña
     * más corta y aun así debe poder iniciar sesión.
     */
    public static function forVerification(string $value): self
    {
        return new self($value, false);
    }

    public function value(): string
    {
        return $this->value;
    }

    /** Evita que la contraseña aparezca en var_dump o en un stack trace. */
    public function __debugInfo(): array
    {
        return ['value' => '***'];
    }

    public function __toString(): string
    {
        return '***';
    }
}
