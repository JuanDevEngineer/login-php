<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pdo;

use App\Infrastructure\Config\Config;

/**
 * Conexión PDO perezosa. Las credenciales llegan por configuración (que las lee
 * del .env), nunca escritas en el código.
 */
final class PdoConnection
{
    private Config $config;
    private ?\PDO $pdo = null;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function pdo(): \PDO
    {
        if ($this->pdo instanceof \PDO) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config->get('db.host'),
            $this->config->get('db.port'),
            $this->config->get('db.name'),
            $this->config->get('db.charset')
        );

        $this->pdo = new \PDO(
            $dsn,
            (string) $this->config->get('db.user'),
            (string) $this->config->get('db.pass'),
            [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                // Sin emulación: los placeholders los resuelve MySQL, no PHP.
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]
        );

        return $this->pdo;
    }
}
