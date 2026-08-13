<?php

declare(strict_types=1);

namespace App\Infrastructure\Clock;

use App\Domain\Port\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }

    public function timestamp(): int
    {
        return time();
    }
}
