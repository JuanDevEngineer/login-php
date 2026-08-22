<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pdo;

use App\Domain\Port\LoginLog;
use App\Domain\ValueObject\UserId;

/** ADAPTADOR MySQL del puerto LoginLog. */
final class PdoLoginLog implements LoginLog
{
    private const FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly PdoConnection $connection)
    {
    }

    public function record(UserId $user, \DateTimeImmutable $at): void
    {
        $stmt = $this->connection->pdo()->prepare(
            'INSERT INTO acceso (usuario_id, fecha) VALUES (:usuario_id, :fecha)'
        );
        $stmt->bindValue(':usuario_id', $user->value(), \PDO::PARAM_INT);
        $stmt->bindValue(':fecha', $at->format(self::FORMAT), \PDO::PARAM_STR);
        $stmt->execute();
    }

    public function countBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        // Extremo superior exclusivo: así el rango del mes es exacto sin tener
        // que restarle un segundo al último día.
        $stmt = $this->connection->pdo()->prepare(
            'SELECT COUNT(*) FROM acceso WHERE fecha >= :desde AND fecha < :hasta'
        );
        $stmt->bindValue(':desde', $from->format(self::FORMAT), \PDO::PARAM_STR);
        $stmt->bindValue(':hasta', $to->format(self::FORMAT), \PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
