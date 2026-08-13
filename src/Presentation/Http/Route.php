<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/** Una entrada de la tabla de rutas. */
final class Route
{
    public string $method;
    public string $path;
    public string $controller;
    public string $action;
    public string $access;

    public const PUBLIC_ACCESS = 'public';
    public const AUTH          = 'auth';
    public const ADMIN         = 'admin';

    public function __construct(
        string $method,
        string $path,
        string $controller,
        string $action,
        string $access = self::PUBLIC_ACCESS
    ) {
        $this->method     = strtoupper($method);
        $this->path       = $path;
        $this->controller = $controller;
        $this->action     = $action;
        $this->access     = $access;
    }
}
