<?php

declare(strict_types=1);

namespace App\Domain\Port;

/**
 * PUERTO de sesión. Aísla $_SESSION para que los casos de uso sean testeables
 * sin arrancar una sesión PHP real.
 */
interface SessionStorage
{
    public function start(): void;

    /** @param mixed $default */
    public function get(string $key, $default = null);

    /** @param mixed $value */
    public function set(string $key, $value): void;

    public function has(string $key): bool;

    public function remove(string $key): void;

    /** Destruye la sesión y su cookie. */
    public function destroy(): void;

    /** Rota el identificador de sesión (defensa contra session fixation). */
    public function regenerate(): void;
}
