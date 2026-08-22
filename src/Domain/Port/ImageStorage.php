<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Application\Dto\UploadedImage;

/**
 * PUERTO de almacenamiento de imágenes de perfil.
 *
 * Devuelve y recibe un IDENTIFICADOR de archivo, no una URL. Antes se guardaba
 * la URL absoluta en base de datos, con lo que mover el proyecto o cambiar
 * BASE_URL rompía todas las fotos ya subidas. La URL se arma al renderizar; en
 * la base solo vive el nombre.
 */
interface ImageStorage
{
    /**
     * Valida y guarda la imagen; devuelve el identificador con el que quedó
     * almacenada (por ejemplo "a3f1...c9.jpg").
     *
     * @throws \App\Domain\Exception\InvalidImageException
     */
    public function store(UploadedImage $image): string;

    /**
     * Borra una imagen previamente almacenada. No lanza si ya no existe:
     * borrar es idempotente.
     */
    public function delete(string $identifier): void;
}
