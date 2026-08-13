<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class AccessDeniedException extends DomainException
{
    public static function create(): self
    {
        return new self('No tenés permiso para realizar esta acción.');
    }
}
