<?php

declare(strict_types=1);

namespace App\Application\UseCase\Role;

use App\Domain\Exception\ProtectedRoleException;
use App\Domain\Exception\RoleInUseException;
use App\Domain\Exception\RoleNotFoundException;
use App\Domain\Port\RoleRepository;
use App\Domain\Port\UserRepository;

final class DeleteRole
{
    private RoleRepository $roles;
    private UserRepository $users;

    public function __construct(RoleRepository $roles, UserRepository $users)
    {
        $this->roles = $roles;
        $this->users = $users;
    }

    /**
     * @throws RoleNotFoundException|ProtectedRoleException|RoleInUseException
     */
    public function execute(string $rawId): void
    {
        $id   = (int) $rawId;
        $role = $this->roles->findById($id);

        if ($role === null) {
            throw RoleNotFoundException::withId($id);
        }

        // Primero la invariante de la entidad: los roles de sistema no se borran.
        $role->ensureDeletable();

        // Después, la regla que necesita mirar fuera del agregado. La clave
        // foránea de `usuario` también lo impediría, pero eso llegaría como un
        // error 500 del motor; atrapado acá el admin lee cuántos usuarios hay.
        $inUse = $this->users->countByRole($id);
        if ($inUse > 0) {
            throw RoleInUseException::withUsers($role->name()->value(), $inUse);
        }

        $this->roles->delete($role);
    }
}
