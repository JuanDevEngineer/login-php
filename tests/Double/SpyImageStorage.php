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
        $url = 'http://test/assets/uploads/' . count($this->stored) . '.jpg';
        $this->stored[] = $url;

        return $url;
    }

    public function delete(string $url): void
    {
        $this->deleted[] = $url;
    }

    /** Archivos guardados que no fueron borrados después. */
    public function orphans(): array
    {
        return array_values(array_diff($this->stored, $this->deleted));
    }
}
