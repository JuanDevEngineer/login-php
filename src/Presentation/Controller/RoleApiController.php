<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Role\CreateRole;
use App\Application\UseCase\Role\DeleteRole;
use App\Application\UseCase\Role\ListRolesDetailed;
use App\Application\UseCase\Role\UpdateRole;
use App\Domain\Exception\DomainException;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * Endpoints JSON de la gestión de roles.
 *
 * Todas las rutas que apuntan acá están declaradas como ADMIN en la tabla del
 * router, así que no hay chequeos de rol repetidos en cada método.
 */
final class RoleApiController extends AbstractController
{
    public function list(Request $request): Response
    {
        // Array pelado: es lo que consume la DataTable como dataSrc.
        return $this->json($this->useCase(ListRolesDetailed::class)->execute());
    }

    public function create(Request $request): Response
    {
        try {
            $role = $this->useCase(CreateRole::class)->execute($request->input('nombre'));
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok([
            'message' => 'Rol creado.',
            'role'    => $role,
        ]);
    }

    public function update(Request $request): Response
    {
        try {
            $role = $this->useCase(UpdateRole::class)->execute(
                $request->input('id'),
                $request->input('nombre')
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok([
            'message' => 'Rol actualizado.',
            'role'    => $role,
        ]);
    }

    public function delete(Request $request): Response
    {
        try {
            $this->useCase(DeleteRole::class)->execute($request->input('id'));
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok(['message' => 'Rol eliminado.']);
    }
}
