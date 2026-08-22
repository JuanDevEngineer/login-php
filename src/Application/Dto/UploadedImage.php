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
     *
     * @throws InvalidImageException con un mensaje que dice qué pasó de verdad.
     *         Antes cualquier fallo devolvía "no se pudo procesar la imagen",
     *         que no le sirve a nadie para saber si el problema es el tamaño,
     *         el tipo o la configuración del servidor.
     */
    public static function fromPhpUpload(?array $file): self
    {
        if ($file === null || !isset($file['error'])) {
            throw InvalidImageException::noFileReceived();
        }

        $error = (int) $file['error'];

        if ($error !== UPLOAD_ERR_OK) {
            throw self::describeError($error);
        }

        if (empty($file['tmp_name'])) {
            throw InvalidImageException::uploadFailed();
        }

        return new self(
            (string) $file['tmp_name'],
            (int) $file['size'],
            (string) ($file['name'] ?? 'upload')
        );
    }

    private static function describeError(int $error): InvalidImageException
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                // El límite lo impone php.ini, no la aplicación: decirlo evita
                // que alguien busque el problema en el código.
                return InvalidImageException::exceedsServerLimit(
                    (string) ini_get('upload_max_filesize')
                );

            case UPLOAD_ERR_FORM_SIZE:
                return InvalidImageException::tooLargeForForm();

            case UPLOAD_ERR_PARTIAL:
                return InvalidImageException::partialUpload();

            case UPLOAD_ERR_NO_FILE:
                return InvalidImageException::noFileReceived();

            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                return InvalidImageException::serverMisconfigured();

            default:
                return InvalidImageException::uploadFailed();
        }
    }
}
