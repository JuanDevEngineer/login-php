<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Domain\Port\SessionStorage;

final class LogoutUser
{
    private SessionStorage $session;

    public function __construct(SessionStorage $session)
    {
        $this->session = $session;
    }

    public function execute(): void
    {
        $this->session->destroy();
    }
}
