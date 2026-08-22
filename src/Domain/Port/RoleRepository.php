<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Entity\Role;
use App\Domain\ValueObject\RoleName;

/** PUERTO de persistencia de roles. */
interface RoleRepository
{
    public function findById(int $id): ?Role;

    public function findByName(RoleName $name): ?Role;

    /** @return Role[] */
    public function findAll(): array;

    /**
     * ¿Ya hay un rol con ese nombre? `$excluding` permite editar un rol sin
     * que choque consigo mismo.
     */
    public function existsWithName(RoleName $name, ?int $excluding = null): bool;

    /** Inserta y devuelve el rol con su id asignado. */
    public function add(Role $role): Role;

    public function save(Role $role): void;

    public function delete(Role $role): void;

    /**
     * Persiste el conjunto de permisos del rol, reemplazando el anterior.
     *
     * Va separado de save() porque toca otra tabla y porque casi todas las
     * escrituras de rol no cambian permisos: acoplarlas obligaría a reescribir
     * el pivote en cada renombrado.
     */
    public function syncPermissions(Role $role): void;
}
