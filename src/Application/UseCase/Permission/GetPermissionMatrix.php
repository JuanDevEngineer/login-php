<?php

declare(strict_types=1);

namespace App\Application\UseCase\Permission;

use App\Domain\Port\RoleRepository;
use App\Domain\ValueObject\Permission;

/**
 * Estructura completa para pintar la matriz de roles × permisos.
 */
final class GetPermissionMatrix
{
    public function __construct(private readonly RoleRepository $roles)
    {
    }

    /**
     * @return array{
     *     groups: array<string, list<array{code: string, label: string, description: string}>>,
     *     roles: list<array{id: int, name: string, system: bool, editable: bool, granted: list<string>, total: int}>
     * }
     */
    public function execute(): array
    {
        $groups = [];
        foreach (Permission::grouped() as $group => $permissions) {
            foreach ($permissions as $permission) {
                $groups[$group][] = [
                    'code'        => $permission->value,
                    'label'       => $permission->label(),
                    'description' => $permission->description(),
                ];
            }
        }

        $roles = [];
        foreach ($this->roles->findAll() as $role) {
            $editable = $role->hasEditablePermissions();

            // ROL_ADMIN se muestra con todo marcado aunque el pivote no lo
            // tuviera: es lo que hace de verdad Role::can().
            $granted = $editable
                ? $role->permissions()->toCodes()
                : Permission::allCodes();

            $roles[] = [
                'id'       => $role->id() ?? 0,
                'name'     => $role->name()->value(),
                'system'   => $role->isSystem(),
                'editable' => $editable,
                'granted'  => $granted,
                'total'    => count($granted),
            ];
        }

        return ['groups' => $groups, 'roles' => $roles];
    }
}
