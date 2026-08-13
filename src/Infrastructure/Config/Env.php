<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

/** Lector de archivos .env sin dependencias externas. */
final class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));

            // Quitar comillas envolventes opcionales.
            $length = strlen($value);
            if ($length >= 2) {
                $first = $value[0];
                $last  = $value[$length - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // El entorno real del servidor tiene prioridad sobre el archivo.
            if (getenv($key) === false) {
                self::$values[$key] = $value;
            }
        }
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $fromSystem = getenv($key);
        if ($fromSystem !== false && $fromSystem !== '') {
            return $fromSystem;
        }

        if (isset(self::$values[$key]) && self::$values[$key] !== '') {
            return self::$values[$key];
        }

        return $default;
    }
}
