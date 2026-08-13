<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Port\SessionStorage;
use App\Domain\Port\TokenGenerator;

final class CsrfGuard
{
    private const SESSION_KEY = '_csrf_token';
    public const FIELD_NAME   = 'csrf_token';

    private SessionStorage $session;
    private TokenGenerator $tokens;

    public function __construct(SessionStorage $session, TokenGenerator $tokens)
    {
        $this->session = $session;
        $this->tokens  = $tokens;
    }

    /** Devuelve el token de la sesión, generándolo la primera vez. */
    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = $this->tokens->generate(32);
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function isValid(?string $candidate): bool
    {
        if (!is_string($candidate) || $candidate === '') {
            return false;
        }

        $expected = $this->session->get(self::SESSION_KEY);
        if (!is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals($expected, $candidate);
    }

    /** Campo oculto listo para pegar dentro de un <form>. */
    public function field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::FIELD_NAME,
            htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8')
        );
    }
}
