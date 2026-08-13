<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidImageException extends DomainException
{
    public static function unsupportedType(string $mime): self
    {
        return new self(sprintf('Tipo de imagen no permitido: %s.', $mime));
    }

    public static function tooLarge(int $maxBytes): self
    {
        return new self(sprintf('La imagen supera el máximo de %d KB.', (int) ($maxBytes / 1024)));
    }

    public static function uploadFailed(): self
    {
        return new self('No se pudo procesar la imagen enviada.');
    }
}
