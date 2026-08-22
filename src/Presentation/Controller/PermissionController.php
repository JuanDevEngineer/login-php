<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Permission\GetPermissionMatrix;
use App\Application\UseCase\Permission\SyncRolePermissions;
use App\Domain\Exception\DomainException;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * Matriz de permisos por rol.
 *
 * El control de acceso lo aplica el router (permisos.gestionar), así que acá no
 * hay comprobaciones repetidas.
 */
final class PermissionController extends AbstractController
{
    public function matrix(Request $request): Response
    {
        return $this->view('pages/roles/permissions', [
            'title'      => 'Permisos por rol',
            'breadcrumb' => ['Roles', 'Permisos'],
            'matrix'     => $this->useCase(GetPermissionMatrix::class)->execute(),
        ]);
    }

    public function sync(Request $request): Response
    {
        // Los checkboxes llegan como permisos[] y pueden no llegar: desmarcar
        // todo significa un array vacío, no la ausencia de cambios.
        $codes = $request->arrayInput('permisos');

        try {
            $applied = $this->useCase(SyncRolePermissions::class)->execute(
                $request->input('rol_id'),
                $codes
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok([
            'message'  => 'Permisos actualizados.',
            'permisos' => $applied,
            'total'    => count($applied),
        ]);
    }
}
