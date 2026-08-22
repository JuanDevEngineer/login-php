<?php

declare(strict_types=1);

namespace Tests\Double;

use App\Domain\Port\LoginLog;
use App\Domain\ValueObject\UserId;

/** Bitácora de accesos en memoria. */
final class InMemoryLoginLog implements LoginLog
{
    /** @var list<array{user: int, at: \DateTimeImmutable}> */
    public array $entries = [];

    /** Si se define, record() lanza: simula un fallo de escritura. */
    public ?\Throwable $failOnRecord = null;

    public function record(UserId $user, \DateTimeImmutable $at): void
    {
        if ($this->failOnRecord !== null) {
            throw $this->failOnRecord;
        }

        $this->entries[] = ['user' => $user->value(), 'at' => $at];
    }

    public function countBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        $count = 0;

        foreach ($this->entries as $entry) {
            // Extremo superior exclusivo, igual que el adaptador real.
            if ($entry['at'] >= $from && $entry['at'] < $to) {
                $count++;
            }
        }

        return $count;
    }
}
