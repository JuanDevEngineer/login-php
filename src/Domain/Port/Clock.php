<?php

declare(strict_types=1);

namespace App\Domain\Port;

/**
 * PUERTO de tiempo. Inyectar el reloj en vez de llamar a time() hace que la
 * expiración de tokens se pueda testear sin esperar 30 minutos.
 */
interface Clock
{
    public function now(): \DateTimeImmutable;

    public function timestamp(): int;
}
