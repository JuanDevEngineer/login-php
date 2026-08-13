<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Entity\Role;

/** Proyección de un rol para las respuestas JSON del gestor. */
final class RoleView
{
    public int $id;
    public string $name;
    public bool $system;
    public int $userCount;

    public function __construct(int $id, string $name, bool $system, int $userCount)
    {
        $this->id        = $id;
        $this->name      = $name;
        $this->system    = $system;
        $this->userCount = $userCount;
    }

    public static function fromEntity(Role $role, int $userCount = 0): self
    {
        return new self(
            $role->id() ?? 0,
            $role->name()->value(),
            $role->isSystem(),
            $userCount
        );
    }

    /**
     * Las claves `id_rol` y `rol_usuario` se mantienen porque son las que ya
     * consumen los <select> de roles en el gestor de usuarios. El resto es
     * información nueva para la tabla de roles.
     */
    public function toArray(): array
    {
        return [
            'id_rol'      => $this->id,
            'rol_usuario' => $this->name,
            'es_sistema'  => $this->system,
            'usuarios'    => $this->userCount,
            'editable'    => !$this->system,
            'eliminable'  => !$this->system && $this->userCount === 0,
        ];
    }
}
