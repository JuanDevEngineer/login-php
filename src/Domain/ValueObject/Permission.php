<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Catálogo de permisos de la aplicación.
 *
 * Es un enum y NO una tabla a propósito. Un permiso solo significa algo si hay
 * código que lo comprueba; si se pudieran inventar desde la interfaz, se
 * acumularían entradas decorativas que no protegen nada y dan una falsa
 * sensación de seguridad. El código es la única fuente de verdad: la base solo
 * guarda qué rol tiene cuál.
 *
 * Para agregar un permiso: añadir el caso acá, mapearlo en label/description/
 * group, y usarlo en la tabla de rutas o donde corresponda.
 */
enum Permission: string
{
    // --- Panel ------------------------------------------------------------
    case PanelVer = 'panel.ver';

    // --- Perfil propio ----------------------------------------------------
    case PerfilEditar = 'perfil.editar';

    // --- Usuarios ---------------------------------------------------------
    case UsuariosVer    = 'usuarios.ver';
    case UsuariosCrear  = 'usuarios.crear';
    case UsuariosEditar = 'usuarios.editar';
    case UsuariosEstado = 'usuarios.cambiar_estado';

    // --- Roles y permisos -------------------------------------------------
    case RolesVer          = 'roles.ver';
    case RolesGestionar    = 'roles.gestionar';
    case PermisosGestionar = 'permisos.gestionar';

    /** Nombre corto para la matriz. */
    public function label(): string
    {
        return match ($this) {
            self::PanelVer          => 'Ver el panel',
            self::PerfilEditar      => 'Editar el perfil propio',
            self::UsuariosVer       => 'Ver usuarios',
            self::UsuariosCrear     => 'Crear usuarios',
            self::UsuariosEditar    => 'Editar usuarios',
            self::UsuariosEstado    => 'Activar y desactivar usuarios',
            self::RolesVer          => 'Ver roles',
            self::RolesGestionar    => 'Crear, renombrar y eliminar roles',
            self::PermisosGestionar => 'Asignar permisos a los roles',
        };
    }

    /** Qué habilita en concreto, para que la matriz no sea adivinanza. */
    public function description(): string
    {
        return match ($this) {
            self::PanelVer          => 'Entrar al panel y ver las métricas de inicio.',
            self::PerfilEditar      => 'Cambiar la propia foto de perfil.',
            self::UsuariosVer       => 'Abrir el gestor y consultar el listado.',
            self::UsuariosCrear     => 'Dar de alta cuentas nuevas.',
            self::UsuariosEditar    => 'Modificar datos y foto de cualquier usuario.',
            self::UsuariosEstado    => 'Bloquear o rehabilitar el acceso de una cuenta.',
            self::RolesVer          => 'Consultar la lista de roles existentes.',
            self::RolesGestionar    => 'Alta, renombrado y borrado de roles.',
            self::PermisosGestionar => 'Cambiar qué puede hacer cada rol. Incluye este mismo permiso.',
        };
    }

    /** Agrupación para pintar la matriz por bloques. */
    public function group(): string
    {
        return match ($this) {
            self::PanelVer                                                     => 'Panel',
            self::PerfilEditar                                                 => 'Perfil',
            self::UsuariosVer, self::UsuariosCrear,
            self::UsuariosEditar, self::UsuariosEstado                         => 'Usuarios',
            self::RolesVer, self::RolesGestionar, self::PermisosGestionar      => 'Roles y permisos',
        };
    }

    /**
     * Permisos agrupados y en orden de presentación.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::cases() as $permission) {
            $grouped[$permission->group()][] = $permission;
        }

        return $grouped;
    }

    /** @return list<string> */
    public static function allCodes(): array
    {
        return array_map(static fn (self $p): string => $p->value, self::cases());
    }

    /**
     * Convierte un código guardado en base de datos. Devuelve null si el código
     * ya no existe en el enum: una fila huérfana de una versión anterior no
     * debe reventar la carga del rol.
     */
    public static function tryFromCode(string $code): ?self
    {
        return self::tryFrom($code);
    }
}
