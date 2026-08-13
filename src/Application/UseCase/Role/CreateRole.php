<?php

declare(strict_types=1);

namespace App\Application\UseCase\Role;

use App\Application\Dto\RoleView;
use App\Domain\Entity\Role;
use App\Domain\Exception\RoleAlreadyExistsException;
use App\Domain\Port\RoleRepository;
use App\Domain\ValueObject\RoleName;

final class CreateRole
{
    private RoleRepository $roles;

    public function __construct(RoleRepository $roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return array<string, mixed>
     * @throws RoleAlreadyExistsException
     */
    public function execute(string $rawName): array
    {
        // RoleName normaliza a mayúsculas y valida el formato; si el nombre no
        // sirve, lanza antes de que lleguemos a la base.
        $name = RoleName::fromString($rawName);

        if ($this->roles->existsWithName($name)) {
            throw RoleAlreadyExistsException::withName($name->value());
        }

        // Un rol creado desde el panel nunca nace como "de sistema": esa marca
        // solo la pone la migración, para ROL_ADMIN y ROL_USER.
        $role = $this->roles->add(Role::create($name));

        return RoleView::fromEntity($role, 0)->toArray();
    }
}
