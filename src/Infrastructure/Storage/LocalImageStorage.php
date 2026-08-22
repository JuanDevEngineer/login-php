<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Dto\UploadedImage;
use App\Domain\Exception\InvalidImageException;
use App\Domain\Port\ImageStorage;

/**
 * Guarda imágenes en assets/uploads y devuelve solo el nombre del archivo.
 *
 * Reglas de seguridad: el tipo se determina con finfo (nunca con el que declara
 * el navegador), la extensión sale de una whitelist indexada por MIME real y el
 * nombre se genera con random_bytes, así el nombre original del cliente nunca
 * toca el sistema de archivos.
 */
final class LocalImageStorage implements ImageStorage
{
    /** MIME real => extensión con la que guardamos. */
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    private string $directory;
    private int $maxBytes;

    public function __construct(string $directory, int $maxBytes)
    {
        $this->directory = rtrim($directory, '/');
        $this->maxBytes  = $maxBytes;
    }

    public function store(UploadedImage $image): string
    {
        if (!is_uploaded_file($image->temporaryPath) && !is_file($image->temporaryPath)) {
            throw InvalidImageException::uploadFailed();
        }

        if ($image->sizeInBytes <= 0) {
            throw InvalidImageException::uploadFailed();
        }
        if ($image->sizeInBytes > $this->maxBytes) {
            throw InvalidImageException::tooLarge($this->maxBytes);
        }

        $mime = $this->detectMimeType($image->temporaryPath);
        if (!isset(self::ALLOWED[$mime])) {
            throw InvalidImageException::unsupportedType($mime);
        }

        // Segunda comprobación: que además de tener MIME de imagen, sea
        // realmente decodificable. Un archivo con cabecera falsificada no pasa.
        if (@getimagesize($image->temporaryPath) === false) {
            throw InvalidImageException::unsupportedType($mime);
        }

        $this->ensureDirectoryExists();

        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mime];
        $target   = $this->directory . '/' . $filename;

        $moved = is_uploaded_file($image->temporaryPath)
            ? move_uploaded_file($image->temporaryPath, $target)
            : rename($image->temporaryPath, $target);

        if ($moved === false) {
            throw InvalidImageException::uploadFailed();
        }

        @chmod($target, 0644);

        return $filename;
    }

    public function delete(string $identifier): void
    {
        if ($identifier === '') {
            return;
        }

        // basename() corta cualquier intento de traversal ("../../index.php").
        $filename = basename($identifier);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return;
        }

        $realTarget    = realpath($this->directory . '/' . $filename);
        $realDirectory = realpath($this->directory);

        if ($realTarget === false || $realDirectory === false) {
            return;
        }

        // Confirmamos que el archivo resuelto sigue dentro del directorio de
        // subidas antes de borrar nada.
        if (strpos($realTarget, $realDirectory . DIRECTORY_SEPARATOR) !== 0) {
            return;
        }

        if (is_file($realTarget)) {
            @unlink($realTarget);
        }
    }

    private function detectMimeType(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);

        return is_string($mime) ? $mime : 'application/octet-stream';
    }

    private function ensureDirectoryExists(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        if (!mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('No se pudo crear el directorio de subidas.');
        }
    }
}
