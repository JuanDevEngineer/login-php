<?php

declare(strict_types=1);

/**
 * Arranque de la aplicación: autoload, configuración, errores y contenedor.
 * No emite salida; devuelve el contenedor ya armado.
 */

use App\Infrastructure\Config\Config;
use App\Infrastructure\Container;

$root = __DIR__;

// --- Autoload -------------------------------------------------------------
// Composer, si está instalado, resuelve las dependencias de terceros
// (PHPMailer, PHPUnit). Encima registramos siempre nuestro autoloader PSR-4:
// así el namespace App\ funciona aunque el mapa de Composer esté desactualizado
// respecto a composer.json — por ejemplo, justo después de reestructurar y
// antes de correr `composer dump-autoload`.
if (is_file($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}

require_once $root . '/autoload.php';

// --- Configuración --------------------------------------------------------
$config = Config::fromEnvironment($root);

date_default_timezone_set((string) $config->get('app.timezone', 'UTC'));

// --- Errores --------------------------------------------------------------
// En producción nunca se muestran: solo se registran. Un stack trace en
// pantalla filtra rutas del servidor y credenciales.
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', $config->isDevelopment() ? '1' : '0');

return new Container($config);
