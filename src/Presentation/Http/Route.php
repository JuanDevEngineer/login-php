<?php

declare(strict_types=1);

namespace App\Presentation\Http;

use App\Domain\ValueObject\Permission;

/**
 * Una entrada de la tabla de rutas.
 *
 * El nivel `admin` desapareció: preguntar "¿sos administrador?" ata el acceso a
 * un rol concreto. Ahora cada ruta protegida declara QUÉ PERMISO exige, y entra
 * quien lo tenga, sea cual sea su rol.
 */
final class Route
{
    public const PUBLIC_ACCESS = 'public';
    public const AUTH          = 'auth';

    public string $method;
    public string $path;
    public string $controller;
    public string $action;

    /** self::PUBLIC_ACCESS, self::AUTH, o un permiso concreto. */
    public string $access;
    public ?Permission $permission;

    /**
     * @param string|Permission $access nivel de acceso, o el permiso exigido
     */
    public function __construct(
        string $method,
        string $path,
        string $controller,
        string $action,
        $access = self::PUBLIC_ACCESS
    ) {
        $this->method     = strtoupper($method);
        $this->path       = $path;
        $this->controller = $controller;
        $this->action     = $action;

        if ($access instanceof Permission) {
            // Exigir un permiso implica sesión: sin usuario no hay permisos.
            $this->access     = self::AUTH;
            $this->permission = $access;
        } else {
            $this->access     = $access;
            $this->permission = null;
        }
    }

    public function isPublic(): bool
    {
        return $this->access === self::PUBLIC_ACCESS && $this->permission === null;
    }

    public function requiresPermission(): bool
    {
        return $this->permission !== null;
    }
}
