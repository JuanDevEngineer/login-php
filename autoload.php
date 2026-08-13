<?php

declare(strict_types=1);

/**
 * Autoloader PSR-4 de reserva.
 *
 * Se usa solamente si todavía no se ejecutó `composer install`. Mapea el
 * namespace App\ a src/ y carga PHPMailer desde libs/, replicando lo que hará
 * Composer una vez instalado.
 */

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\'                  => __DIR__ . '/src/',
        'PHPMailer\\PHPMailer\\' => __DIR__ . '/libs/PHPMailer-master/src/',
        'Tests\\'                => __DIR__ . '/tests/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $length = strlen($prefix);
        if (strncmp($class, $prefix, $length) !== 0) {
            continue;
        }

        $relative = substr($class, $length);
        $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// Helpers globales de las plantillas (e(), json_attr()).
require_once __DIR__ . '/src/Presentation/View/helpers.php';
