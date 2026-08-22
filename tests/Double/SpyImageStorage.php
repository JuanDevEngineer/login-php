<?php

declare(strict_types=1);

namespace Tests\Double;

use App\Application\Dto\UploadedImage;
use App\Domain\Port\ImageStorage;

/** Storage falso que registra qué se guardó y qué se borró. */
final class SpyImageStorage implements ImageStorage
{
    /** @var string[] */
    public array $stored = [];
    /** @var string[] */
    public array $deleted = [];

    public function store(UploadedImage $image): string
    {
        // El puerto devuelve el NOMBRE del archivo, no una URL.
        $filename = 'avatar-' . count($this->stored) . '.jpg';
        $this->stored[] = $filename;

        return $filename;
    }

    public function delete(string $identifier): void
    {
        $this->deleted[] = $identifier;
    }

    /** Archivos guardados que no fueron borrados después. */
    public function orphans(): array
    {
        return array_values(array_diff($this->stored, $this->deleted));
    }
}
