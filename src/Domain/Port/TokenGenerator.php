<?php

declare(strict_types=1);

namespace App\Domain\Port;

/** PUERTO de material aleatorio criptográficamente seguro. */
interface TokenGenerator
{
    /** Cadena hexadecimal impredecible de $bytes bytes de entropía. */
    public function generate(int $bytes = 16): string;
}
