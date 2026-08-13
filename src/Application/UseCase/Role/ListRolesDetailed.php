<?php

declare(strict_types=1);

namespace App\Application\UseCase\Role;

use App\Application\Dto\RoleView;
use App\Domain\Port\RoleRepository;
use App\Domain\Port\UserRepository;

/**
 * Listado de roles para la tabla de gestión, con el número de usuarios que
 * tiene cada uno. Se separa de ListRoles (que solo alimenta los <select>) para
 * no pagar el conteo en cada carga de un formulario.
 */
final class ListRolesDetailed
{
    private RoleRepository $roles;
    private UserRepository $users;

    public function __construct(RoleRepository $roles, UserRepository $users)
    {
        $this->roles = $roles;
        $this->users = $users;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        $result = [];

        foreach ($this->roles->findAll() as $role) {
            $count = $role->id() !== null ? $this->users->countByRole($role->id()) : 0;
            $result[] = RoleView::fromEntity($role, $count)->toArray();
        }

        return $result;
    }
}
