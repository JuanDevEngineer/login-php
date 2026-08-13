<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\ProtectedRoleException;
use App\Domain\ValueObject\RoleName;

/**
 * Un rol del sistema.
 *
 * Los roles marcados como "de sistema" (ROL_ADMIN, ROL_USER) están cableados
 * en el código y la propia entidad se niega a renombrarlos o a dejarse
 * eliminar. La protección vive acá, en el dominio, y no en el controlador: así
 * da igual desde qué caso de uso se intente, la regla se aplica siempre.
 */
final class Role
{
    private ?int $id;
    private RoleName $name;
    private bool $system;

    public function __construct(?int $id, RoleName $name, bool $system = false)
    {
        $this->id     = $id;
        $this->name   = $name;
        $this->system = $system;
    }

    /** Rol nuevo creado por un administrador: nunca es de sistema. */
    public static function create(RoleName $name): self
    {
        return new self(null, $name, false);
    }

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

    /**
     * @throws ProtectedRoleException
     */
    public function rename(RoleName $name): void
    {
        $this->guardNotSystem();
        $this->name = $name;
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
