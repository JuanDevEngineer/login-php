<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Exception\InvalidImageException;

/**
 * Envoltorio de un archivo subido. Normaliza $_FILES para que ni el caso de uso
 * ni el puerto de almacenamiento tengan que conocer esa superglobal.
 */
final class UploadedImage
{
    public string $temporaryPath;
    public int $sizeInBytes;
    public string $clientFilename;

    public function __construct(string $temporaryPath, int $sizeInBytes, string $clientFilename)
    {
        $this->temporaryPath  = $temporaryPath;
        $this->sizeInBytes    = $sizeInBytes;
        $this->clientFilename = $clientFilename;
    }

    /**
     * @param array<string, mixed>|null $file entrada de $_FILES
     */
    public static function fromPhpUpload(?array $file): self
    {
        if ($file === null || !isset($file['error'])) {
            throw InvalidImageException::uploadFailed();
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw InvalidImageException::uploadFailed();
        }

        return new self(
            (string) $file['tmp_name'],
            (int) $file['size'],
            (string) ($file['name'] ?? 'upload')
        );
    }
}
