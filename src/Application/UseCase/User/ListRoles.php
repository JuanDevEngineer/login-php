<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Domain\Port\RoleRepository;

final class ListRoles
{
    private RoleRepository $roles;

    public function __construct(RoleRepository $roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return array<int, array{id_rol: int, rol_usuario: string}>
     */
    public function execute(): array
    {
        return array_map(
            static fn ($role) => [
                'id_rol'      => $role->id(),
                'rol_usuario' => $role->name()->value(),
            ],
            $this->roles->findAll()
        );
    }
}
