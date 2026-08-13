<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Password\RequestPasswordReset;
use App\Application\UseCase\Password\ResetPassword;
use App\Application\UseCase\Password\ValidateRecoveryToken;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\InvalidRecoveryTokenException;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class PasswordController extends AbstractController
{
    public function showForgot(Request $request): Response
    {
        return $this->view('pages/auth/forgot-password', ['title' => 'Recuperar contraseña']);
    }

    public function sendLink(Request $request): Response
    {
        try {
            $this->useCase(RequestPasswordReset::class)->execute($request->input('email'));
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        } catch (\RuntimeException $e) {
            // Falló el SMTP. No revelamos el detalle al cliente.
            error_log('[password] ' . $e->getMessage());
        }

        // Respuesta idéntica exista o no la cuenta: no permitimos enumerar correos.
        return $this->ok([
            'message'  => 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.',
            'redirect' => $this->baseUrl() . '/login',
        ]);
    }

    public function showReset(Request $request): Response
    {
        $selector = $request->query('selector');
        $verifier = $request->query('verifier');

        try {
            $this->useCase(ValidateRecoveryToken::class)->execute($selector, $verifier);
        } catch (InvalidRecoveryTokenException $e) {
            return $this->view('pages/auth/reset-invalid', [
                'title'   => 'Enlace inválido',
                'message' => $e->getMessage(),
            ], 410);
        }

        return $this->view('pages/auth/reset-password', [
            'title'    => 'Nueva contraseña',
            'selector' => $selector,
            'verifier' => $verifier,
        ]);
    }

    public function reset(Request $request): Response
    {
        try {
            // El token se revalida dentro del caso de uso; el formulario no
            // manda un id de usuario que se pudiera manipular.
            $this->useCase(ResetPassword::class)->execute(
                $request->input('selector'),
                $request->input('verifier'),
                $request->raw('password')
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok([
            'message'  => 'Contraseña actualizada. Iniciá sesión con la nueva.',
            'redirect' => $this->baseUrl() . '/login',
        ]);
    }
}
