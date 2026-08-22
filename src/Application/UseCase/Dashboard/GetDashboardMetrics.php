<?php

declare(strict_types=1);

namespace App\Application\UseCase\Dashboard;

use App\Application\Dto\DashboardMetrics;
use App\Domain\Port\Clock;
use App\Domain\Port\LoginLog;
use App\Domain\Port\UserRepository;

/**
 * Reúne las cifras del panel.
 *
 * El "mes en curso" se calcula desde el Clock inyectado, no con date() ni con
 * NOW() en SQL: así el cálculo es testeable sin esperar a que cambie el mes y
 * no depende de la zona horaria del motor de base de datos, que puede diferir
 * de la de PHP.
 */
final class GetDashboardMetrics
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly LoginLog $logins,
        private readonly Clock $clock,
    ) {
    }

    public function execute(): DashboardMetrics
    {
        [$from, $to] = $this->currentMonthRange();

        return new DashboardMetrics(
            $this->users->count(),
            $this->users->count(false),
            $this->logins->countBetween($from, $to),
            $from
        );
    }

    /**
     * Intervalo [primer día del mes 00:00, primer día del mes siguiente 00:00).
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function currentMonthRange(): array
    {
        // Una sola lectura del reloj: si se consultara varias veces, dos
        // llamadas podrían caer a distinto lado de un cambio de minuto, mes
        // o año y el rango saldría incoherente.
        $now = $this->clock->now();

        $from = $now
            ->setDate((int) $now->format('Y'), (int) $now->format('n'), 1)
            ->setTime(0, 0, 0);

        // modify('+1 month') sobre el día 1 nunca desborda: no existe el
        // problema del 31 de enero + 1 mes.
        $to = $from->modify('+1 month');

        return [$from, $to];
    }
}
