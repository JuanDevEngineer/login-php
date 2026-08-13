<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;

final class HashedPassword
{
    private string $hash;

    private function __construct(string $hash)
    {
        if ($hash === '') {
            throw new InvalidArgumentException('El hash de contraseña no puede estar vacío.');
        }
        $this->hash = $hash;
    }

    /** Rehidrata un hash ya existente en base de datos. */
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function value(): string
    {
        return $this->hash;
    }

    public function __debugInfo(): array
    {
        return ['hash' => '***'];
    }

    public function __toString(): string
    {
        return '***';
    }
}
