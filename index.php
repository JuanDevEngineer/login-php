<?php

declare(strict_types=1);

/**
 * Front controller. Único punto de entrada de la aplicación:
 * arranca, enruta, responde.
 */

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;
use App\Presentation\Http\Router;

/** @var \App\Infrastructure\Container $container */
$container = require __DIR__ . '/bootstrap.php';

try {
    $response = (new Router($container))->dispatch(Request::fromGlobals());
} catch (\Throwable $e) {
    error_log(sprintf(
        '[%s] %s en %s:%d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    if ($container->config()->isDevelopment()) {
        throw $e;
    }

    $response = Response::html(
        $container->view()->render('errors/500'),
        500
    );
}

$response->send();
