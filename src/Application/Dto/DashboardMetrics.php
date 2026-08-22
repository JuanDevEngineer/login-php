<?php

declare(strict_types=1);

namespace App\Application\Dto;

/** Cifras que alimentan las cards del panel. */
final class DashboardMetrics
{
    public function __construct(
        public readonly int $totalUsers,
        public readonly int $inactiveUsers,
        public readonly int $loginsThisMonth,
        public readonly \DateTimeImmutable $monthStart,
    ) {
    }

    /** Usuarios activos, derivado: no hace falta una consulta más. */
    public function activeUsers(): int
    {
        return max(0, $this->totalUsers - $this->inactiveUsers);
    }

    /** Porcentaje de cuentas inactivas, para el texto de apoyo de la card. */
    public function inactiveRatio(): float
    {
        if ($this->totalUsers === 0) {
            return 0.0;
        }

        return round(($this->inactiveUsers / $this->totalUsers) * 100, 1);
    }

    /** Nombre del mes en curso en español, para la etiqueta de la card. */
    public function monthLabel(): string
    {
        $meses = [
            1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
        ];

        $mes = (int) $this->monthStart->format('n');

        return ($meses[$mes] ?? '') . ' ' . $this->monthStart->format('Y');
    }
}
