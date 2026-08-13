<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidRecoveryTokenException extends DomainException
{
    public static function create(): self
    {
        return new self('El enlace de recuperación es inválido o ya expiró.');
    }
}
