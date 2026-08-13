<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class DashboardController extends AbstractController
{
    public function index(Request $request): Response
    {
        return $this->view('pages/users/dashboard', [
            'title'     => 'Inicio',
            'breadcrumb'=> ['Inicio'],
        ]);
    }

    public function profile(Request $request): Response
    {
        return $this->view('pages/users/profile', [
            'title'      => 'Mi perfil',
            'breadcrumb' => ['Perfil'],
        ]);
    }

    public function manageUsers(Request $request): Response
    {
        return $this->view('pages/users/manage', [
            'title'      => 'Gestor de usuarios',
            'breadcrumb' => ['Usuarios', 'Gestor'],
        ]);
    }

    public function manageRoles(Request $request): Response
    {
        return $this->view('pages/roles/manage', [
            'title'      => 'Gestor de roles',
            'breadcrumb' => ['Roles'],
        ]);
    }
}
