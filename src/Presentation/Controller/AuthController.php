<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\Auth\LogoutUser;
use App\Application\UseCase\Auth\RegisterUser;
use App\Domain\Exception\DomainException;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class AuthController extends AbstractController
{
    public function showLogin(Request $request): Response
    {
        if ($this->user !== null) {
            return $this->redirect('/dashboard');
        }

        return $this->view('pages/auth/login', ['title' => 'Iniciar sesión']);
    }

    public function showRegister(Request $request): Response
    {
        if ($this->user !== null) {
            return $this->redirect('/dashboard');
        }

        return $this->view('pages/auth/register', ['title' => 'Crear cuenta']);
    }

    public function login(Request $request): Response
    {
        try {
            $user = $this->useCase(LoginUser::class)->execute(
                $request->input('username'),
                $request->raw('password')
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), 401);
        }

        return $this->ok([
            'redirect' => $this->baseUrl() . '/dashboard',
            'user'     => ['username' => $user->username],
        ]);
    }

    public function register(Request $request): Response
    {
        try {
            $this->useCase(RegisterUser::class)->execute(
                $request->input('username'),
                $request->input('email'),
                $request->raw('password')
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok([
            'message'  => 'Cuenta creada. Ya podés iniciar sesión.',
            'redirect' => $this->baseUrl() . '/login',
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->useCase(LogoutUser::class)->execute();

        return $this->redirect('/login');
    }
}
