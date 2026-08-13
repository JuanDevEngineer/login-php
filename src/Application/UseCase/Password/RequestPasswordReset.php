<?php

declare(strict_types=1);

namespace App\Application\UseCase\Password;

use App\Domain\Port\Clock;
use App\Domain\Port\Mailer;
use App\Domain\Port\TokenGenerator;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\RecoveryToken;

final class RequestPasswordReset
{
    private UserRepository $users;
    private TokenGenerator $tokens;
    private Mailer $mailer;
    private Clock $clock;
    private string $baseUrl;

    public function __construct(
        UserRepository $users,
        TokenGenerator $tokens,
        Mailer $mailer,
        Clock $clock,
        string $baseUrl
    ) {
        $this->users   = $users;
        $this->tokens  = $tokens;
        $this->mailer  = $mailer;
        $this->clock   = $clock;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Siempre termina sin error aunque el correo no exista: informar lo
     * contrario permitiría enumerar cuentas registradas.
     */
    public function execute(string $rawEmail): void
    {
        $email = Email::fromString($rawEmail);
        $user  = $this->users->findByEmail($email);

        if ($user === null) {
            return;
        }

        $selector = $this->tokens->generate(8);
        $verifier = $this->tokens->generate(32);

        $token = RecoveryToken::issue($selector, $verifier, $this->clock->timestamp());
        $user->startPasswordRecovery($token);
        $this->users->save($user);

        $link = sprintf(
            '%s/password/reset?selector=%s&verifier=%s',
            $this->baseUrl,
            rawurlencode($selector),
            rawurlencode($verifier)
        );

        $this->mailer->send(
            $email,
            'Recuperación de contraseña',
            $this->buildBody($link)
        );
    }

    private function buildBody(string $link): string
    {
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        $minutes  = (int) (RecoveryToken::TTL_SECONDS / 60);

        return <<<HTML
            <p>Recibimos una solicitud para restablecer tu contraseña.</p>
            <p><a href="{$safeLink}">Restablecer mi contraseña</a></p>
            <p>El enlace vence en {$minutes} minutos y solo puede usarse una vez.</p>
            <p>Si no fuiste vos, podés ignorar este mensaje: tu contraseña no cambió.</p>
            HTML;
    }
}
