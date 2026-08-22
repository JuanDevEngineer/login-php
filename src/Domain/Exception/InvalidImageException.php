<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidImageException extends DomainException
{
    public static function unsupportedType(string $mime): self
    {
        return new self(sprintf(
            'Ese archivo no es una imagen admitida (%s). Usá JPG, PNG, GIF o WebP.',
            $mime
        ));
    }

    public static function tooLarge(int $maxBytes): self
    {
        return new self(sprintf(
            'La imagen supera el máximo permitido de %s.',
            self::humanSize($maxBytes)
        ));
    }

    /** El archivo excede upload_max_filesize de php.ini. */
    public static function exceedsServerLimit(string $iniLimit): self
    {
        return new self(sprintf(
            'La imagen supera el límite del servidor (%s). Subí una más liviana '
            . 'o aumentá upload_max_filesize y post_max_size en php.ini.',
            $iniLimit !== '' ? $iniLimit : 'configurado en php.ini'
        ));
    }

    public static function tooLargeForForm(): self
    {
        return new self('La imagen supera el tamaño máximo permitido por el formulario.');
    }

    public static function partialUpload(): self
    {
        return new self('La imagen se subió a medias. Probá de nuevo.');
    }

    public static function noFileReceived(): self
    {
        return new self('No llegó ninguna imagen. Elegí un archivo antes de subir.');
    }

    /**
     * Falta el directorio temporal, no hay permiso de escritura, o una
     * extensión de PHP abortó la subida. No es culpa de quien sube.
     */
    public static function serverMisconfigured(): self
    {
        return new self(
            'El servidor no pudo guardar la imagen. Revisá los permisos del '
            . 'directorio de subidas y la configuración de PHP.'
        );
    }

    public static function uploadFailed(): self
    {
        return new self('No se pudo procesar la imagen enviada.');
    }

    private static function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1024) . ' KB';
    }
}
