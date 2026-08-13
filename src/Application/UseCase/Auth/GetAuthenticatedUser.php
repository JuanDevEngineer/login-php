<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Dto\AuthenticatedUser;
use App\Domain\Port\SessionStorage;

/** Lee de la sesión quién está autenticado, si es que hay alguien. */
final class GetAuthenticatedUser
{
    private SessionStorage $session;

    public function __construct(SessionStorage $session)
    {
        $this->session = $session;
    }

    public function execute(): ?AuthenticatedUser
    {
        $data = $this->session->get(LoginUser::SESSION_KEY);

        if (!is_array($data) || empty($data['id'])) {
            return null;
        }

        return AuthenticatedUser::fromArray($data);
    }
}
