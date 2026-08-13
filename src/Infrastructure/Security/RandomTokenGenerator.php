<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Port\TokenGenerator;

final class RandomTokenGenerator implements TokenGenerator
{
    public function generate(int $bytes = 16): string
    {
        if ($bytes < 1) {
            throw new \InvalidArgumentException('Se requiere al menos 1 byte de entropía.');
        }

        // random_bytes() usa el CSPRNG del sistema. Nunca rand()/mt_rand().
        return bin2hex(random_bytes($bytes));
    }
}
