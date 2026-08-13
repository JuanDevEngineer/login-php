<?php

declare(strict_types=1);

namespace App\Application\UseCase\Password;

use App\Domain\Entity\User;
use App\Domain\Exception\InvalidRecoveryTokenException;
use App\Domain\Port\Clock;
use App\Domain\Port\UserRepository;

/**
 * Comprueba que el par selector/verificador de la URL corresponde a un token
 * vigente y devuelve el usuario dueño del token.
 */
final class ValidateRecoveryToken
{
    private UserRepository $users;
    private Clock $clock;

    public function __construct(UserRepository $users, Clock $clock)
    {
        $this->users = $users;
        $this->clock = $clock;
    }

    /**
     * @throws InvalidRecoveryTokenException
     */
    public function execute(string $selector, string $verifier): User
    {
        if ($selector === '' || $verifier === '') {
            throw InvalidRecoveryTokenException::create();
        }

        $user = $this->users->findByRecoverySelector($selector);
        if ($user === null) {
            throw InvalidRecoveryTokenException::create();
        }

        $token = $user->recoveryToken();
        if ($token === null) {
            throw InvalidRecoveryTokenException::create();
        }

        if ($token->isExpired($this->clock->timestamp())) {
            // Limpiamos el token vencido para no dejar basura en la tabla.
            $user->clearPasswordRecovery();
            $this->users->save($user);
            throw InvalidRecoveryTokenException::create();
        }

        if (!$token->matches($verifier)) {
            throw InvalidRecoveryTokenException::create();
        }

        return $user;
    }
}
