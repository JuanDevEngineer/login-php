<?php

declare(strict_types=1);

namespace App\Application\UseCase\Role;

use App\Application\Dto\RoleView;
use App\Domain\Exception\ProtectedRoleException;
use App\Domain\Exception\RoleAlreadyExistsException;
use App\Domain\Exception\RoleNotFoundException;
use App\Domain\Port\RoleRepository;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\RoleName;

final class UpdateRole
{
    private RoleRepository $roles;
    private UserRepository $users;

    public function __construct(RoleRepository $roles, UserRepository $users)
    {
        $this->roles = $roles;
        $this->users = $users;
    }

    /**
     * @return array<string, mixed>
     * @throws RoleNotFoundException|RoleAlreadyExistsException|ProtectedRoleException
     */
    public function execute(string $rawId, string $rawName): array
    {
        $id   = (int) $rawId;
        $role = $this->roles->findById($id);

        if ($role === null) {
            throw RoleNotFoundException::withId($id);
        }

        $name = RoleName::fromString($rawName);

        // Excluimos el propio rol: si no, renombrarlo a sí mismo chocaría.
        if ($this->roles->existsWithName($name, $id)) {
            throw RoleAlreadyExistsException::withName($name->value());
        }

        // rename() lanza ProtectedRoleException si el rol es de sistema. La
        // regla vive en la entidad, no acá.
        $role->rename($name);

        $this->roles->save($role);

        return RoleView::fromEntity($role, $this->users->countByRole($id))->toArray();
    }
}
