<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\ValueObject\UserId;

/**
 * PUERTO del registro de accesos.
 *
 * Va aparte de UserRepository a propósito: no es persistencia del agregado
 * Usuario, es una bitácora con su propio ciclo de vida y su propio volumen.
 * Mezclarlo obligaría a UserRepository a cargar con responsabilidades ajenas.
 */
interface LoginLog
{
    /** Deja constancia de una autenticación exitosa. */
    public function record(UserId $user, \DateTimeImmutable $at): void;

    /**
     * Accesos ocurridos en el intervalo [$from, $to).
     *
     * El extremo superior es exclusivo para que "el mes en curso" se exprese
     * como [1 del mes 00:00, 1 del mes siguiente 00:00) y no haya que razonar
     * sobre el último segundo del último día.
     */
    public function countBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int;
}
