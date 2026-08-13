<?php

declare(strict_types=1);

namespace Tests\Double;

use App\Domain\Port\Clock;

final class FixedClock implements Clock
{
    private \DateTimeImmutable $now;

    public function __construct(string $when = '2026-01-15 10:00:00')
    {
        $this->now = new \DateTimeImmutable($when);
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function timestamp(): int
    {
        return $this->now->getTimestamp();
    }
}
