<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Dto\UploadedImage;
use App\Domain\Exception\InvalidImageException;
use App\Domain\Port\ImageStorage;

/**
 * Guarda imágenes en assets/uploads.
 *
 * Reglas: el tipo se determina con finfo (nunca con el que declara el
 * navegador), la extensión sale de una whitelist indexada por MIME real y el
 * nombre del archivo se genera con random_bytes, así el nombre original del
 * cliente nunca toca el sistema de archivos.
 */
final class LocalImageStorage implements ImageStorage
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/pjpeg'=> 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    private string $directory;
    private string $publicBaseUrl;
    private int $maxBytes;

    public function __construct(string $directory, string $publicBaseUrl, int $maxBytes)
    {
        $this->directory     = rtrim($directory, '/');
        $this->publicBaseUrl = rtrim($publicBaseUrl, '/');
        $this->maxBytes      = $maxBytes;
    }

    public function store(UploadedImage $image): string
    {
        if (!is_uploaded_file($image->temporaryPath) && !is_file($image->temporaryPath)) {
            throw InvalidImageException::uploadFailed();
        }

        if ($image->sizeInBytes <= 0 || $image->sizeInBytes > $this->maxBytes) {
            throw InvalidImageException::tooLarge($this->maxBytes);
        }

        $mime = $this->detectMimeType($image->temporaryPath);
        if (!isset(self::ALLOWED[$mime])) {
            throw InvalidImageException::unsupportedType($mime);
        }

        // Segunda comprobación: que realmente sea una imagen decodificable.
        if (getimagesize($image->temporaryPath) === false) {
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

        return $this->publicBaseUrl . '/assets/uploads/' . $filename;
    }

    public function delete(string $url): void
    {
        $prefix = $this->publicBaseUrl . '/assets/uploads/';

        // Solo borramos cosas que este mismo storage generó. Si la URL no tiene
        // nuestro prefijo, no es nuestra y no la tocamos.
        if (strpos($url, $prefix) !== 0) {
            return;
        }

        $filename = substr($url, strlen($prefix));

        // basename() corta cualquier intento de traversal ("../../index.php").
        // Aunque la URL la generamos nosotros, este método es público y no
        // conviene confiar en el argumento.
        $filename = basename($filename);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return;
        }

        $target = $this->directory . '/' . $filename;

        // Confirmamos que el archivo resuelto sigue estando dentro del
        // directorio de uploads antes de borrar nada.
        $realTarget    = realpath($target);
        $realDirectory = realpath($this->directory);

        if ($realTarget === false || $realDirectory === false) {
            return;
        }
        if (strpos($realTarget, $realDirectory . DIRECTORY_SEPARATOR) !== 0) {
            return;
        }

        // Borrar es idempotente: que el archivo ya no esté no es un error.
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
            throw new \RuntimeException('No se pudo crear el directorio de uploads.');
        }
    }
}
