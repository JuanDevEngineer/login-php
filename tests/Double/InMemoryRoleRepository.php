<?php

declare(strict_types=1);

namespace Tests\Double;

use App\Domain\Entity\Role;
use App\Domain\Exception\RoleAlreadyExistsException;
use App\Domain\Port\RoleRepository;
use App\Domain\ValueObject\RoleName;

final class InMemoryRoleRepository implements RoleRepository
{
    /** @var array<int, Role> */
    private array $roles = [];
    private int $nextId = 1;

    public function __construct()
    {
        // Los dos roles de sistema, igual que los deja la migración.
        $this->roles[1] = new Role(1, RoleName::admin(), true);
        $this->roles[2] = new Role(2, RoleName::user(), true);
        $this->nextId = 3;
    }

    public function findById(int $id): ?Role
    {
        return $this->roles[$id] ?? null;
    }

    public function findByName(RoleName $name): ?Role
    {
        foreach ($this->roles as $role) {
            if ($role->name()->equals($name)) {
                return $role;
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return array_values($this->roles);
    }

    public function existsWithName(RoleName $name, ?int $excluding = null): bool
    {
        foreach ($this->roles as $id => $role) {
            if ($role->name()->equals($name) && $id !== $excluding) {
                return true;
            }
        }
        return false;
    }

    public function add(Role $role): Role
    {
        if ($this->existsWithName($role->name())) {
            throw RoleAlreadyExistsException::withName($role->name()->value());
        }

        $id = $this->nextId++;
        $stored = new Role($id, $role->name(), false);
        $this->roles[$id] = $stored;

        return $stored;
    }

    public function save(Role $role): void
    {
        if ($role->id() !== null && isset($this->roles[$role->id()])) {
            $this->roles[$role->id()] = $role;
        }
    }

    public function delete(Role $role): void
    {
        if ($role->id() !== null) {
            unset($this->roles[$role->id()]);
        }
    }

    public function count(): int
    {
        return count($this->roles);
    }
}
