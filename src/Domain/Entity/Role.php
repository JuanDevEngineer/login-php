<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\ProtectedRoleException;
use App\Domain\ValueObject\Permission;
use App\Domain\ValueObject\PermissionSet;
use App\Domain\ValueObject\RoleName;

/**
 * Un rol del sistema y lo que puede hacer.
 *
 * Los roles marcados como "de sistema" (ROL_ADMIN, ROL_USER) están cableados en
 * el código y la propia entidad se niega a renombrarlos o a dejarse eliminar.
 * La protección vive acá, en el dominio, y no en el controlador: así da igual
 * desde qué caso de uso se intente, la regla se aplica siempre.
 */
final class Role
{
    private ?int $id;
    private RoleName $name;
    private bool $system;
    private PermissionSet $permissions;

    public function __construct(
        ?int $id,
        RoleName $name,
        bool $system = false,
        ?PermissionSet $permissions = null
    ) {
        $this->id          = $id;
        $this->name        = $name;
        $this->system      = $system;
        $this->permissions = $permissions ?? PermissionSet::empty();
    }

    /** Rol nuevo creado por un administrador: nunca es de sistema. */
    public static function create(RoleName $name): self
    {
        return new self(null, $name, false, PermissionSet::empty());
    }

    // ---------------------------------------------------------------- lecturas

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): RoleName
    {
        return $this->name;
    }

    public function isAdmin(): bool
    {
        return $this->name->isAdmin();
    }

    /** ¿Es uno de los roles de los que depende el código? */
    public function isSystem(): bool
    {
        return $this->system;
    }

    public function permissions(): PermissionSet
    {
        return $this->permissions;
    }

    /**
     * ÚNICO punto donde se decide si un rol puede hacer algo.
     *
     * ROL_ADMIN pasa siempre, sin mirar el conjunto. Es la red de seguridad
     * deliberada: si alguien desmarcara por error el permiso de gestionar
     * permisos, sin este atajo no quedaría forma de entrar a corregirlo salvo
     * por consola de MySQL.
     */
    public function can(Permission $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->permissions->has($permission);
    }

    /**
     * ¿Este rol se administra desde la matriz?
     *
     * ROL_ADMIN no: tiene acceso total por definición y marcar o desmarcar
     * casillas no cambiaría nada. La interfaz lo muestra bloqueado.
     */
    public function hasEditablePermissions(): bool
    {
        return !$this->isAdmin();
    }

    // ----------------------------------------------------------- comportamiento

    /**
     * @throws ProtectedRoleException
     */
    public function rename(RoleName $name): void
    {
        $this->guardNotSystem();
        $this->name = $name;
    }

    /**
     * Reemplaza el conjunto completo de permisos.
     *
     * Sobre ROL_ADMIN no tiene efecto: can() lo ignora igualmente, y aceptar el
     * cambio dejaría la base diciendo una cosa y el comportamiento otra.
     *
     * @throws ProtectedRoleException
     */
    public function changePermissions(PermissionSet $permissions): void
    {
        if (!$this->hasEditablePermissions()) {
            throw ProtectedRoleException::permissionsAreImplicit($this->name->value());
        }

        $this->permissions = $permissions;
    }

    /**
     * Se llama antes de borrar. No borra nada por sí misma: solo hace valer la
     * invariante de que un rol de sistema no se elimina.
     *
     * @throws ProtectedRoleException
     */
    public function ensureDeletable(): void
    {
        $this->guardNotSystem();
    }

    private function guardNotSystem(): void
    {
        if ($this->system) {
            throw ProtectedRoleException::forRole($this->name->value());
        }
    }
}
