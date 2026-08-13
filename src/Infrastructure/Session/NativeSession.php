<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

use App\Domain\Port\SessionStorage;

/** Adaptador sobre $_SESSION, con la cookie endurecida. */
final class NativeSession implements SessionStorage
{
    private bool $started = false;

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        if (PHP_SAPI === 'cli') {
            // En CLI no hay sesión: trabajamos sobre el array a secas.
            $this->started = true;
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $this->isHttps(),
            'httponly' => true,   // inaccesible desde JavaScript
            'samesite' => 'Lax',  // no viaja en peticiones cross-site
        ]);

        session_start();
        $this->started = true;
    }

    public function get(string $key, $default = null)
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();

        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        $this->start();

        $_SESSION = [];

        if (PHP_SAPI === 'cli') {
            return;
        }

        // Borrar también la cookie del navegador, no solo los datos.
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
        $this->started = false;
    }

    public function regenerate(): void
    {
        $this->start();

        if (PHP_SAPI !== 'cli') {
            session_regenerate_id(true);
        }
    }

    private function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}
