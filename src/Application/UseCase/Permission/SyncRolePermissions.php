<?php

declare(strict_types=1);

namespace App\Application\UseCase\Permission;

use App\Domain\Exception\RoleNotFoundException;
use App\Domain\Port\RoleRepository;
use App\Domain\ValueObject\Permission;
use App\Domain\ValueObject\PermissionSet;

/**
 * Reemplaza el conjunto de permisos de un rol.
 *
 * Recibe los códigos crudos del formulario. Los que no existen en el enum se
 * descartan en PermissionSet::fromCodes(): nadie puede otorgar un permiso
 * inventado enviando un checkbox falso.
 */
final class SyncRolePermissions
{
    public function __construct(private readonly RoleRepository $roles)
    {
    }

    /**
     * @param list<string> $codes
     * @return list<string> los códigos efectivamente aplicados
     *
     * @throws RoleNotFoundException
     * @throws \App\Domain\Exception\ProtectedRoleException
     */
    public function execute(string $rawRoleId, array $codes): array
    {
        $id   = (int) $rawRoleId;
        $role = $this->roles->findById($id);

        if ($role === null) {
            throw RoleNotFoundException::withId($id);
        }

        // changePermissions() rechaza ROL_ADMIN: sus permisos son implícitos y
        // aceptar el cambio dejaría la base diciendo una cosa y can() otra.
        $role->changePermissions(PermissionSet::fromCodes($codes));

        $this->roles->syncPermissions($role);

        return $role->permissions()->toCodes();
    }

    /** @return list<string> */
    public function catalogue(): array
    {
        return Permission::allCodes();
    }
}
