<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Application\Dto\UploadedImage;

/** PUERTO de almacenamiento de imágenes de perfil. */
interface ImageStorage
{
    /**
     * Valida y guarda la imagen; devuelve la URL pública resultante.
     *
     * @throws \App\Domain\Exception\InvalidImageException
     */
    public function store(UploadedImage $image): string;

    /**
     * Borra una imagen previamente almacenada, identificada por la URL que
     * devolvió store(). Se usa para no dejar archivos huérfanos cuando la
     * operación que iba a referenciarlos falla después de subirla.
     *
     * No lanza si el archivo ya no existe: borrar es idempotente.
     */
    public function delete(string $url): void;
}
