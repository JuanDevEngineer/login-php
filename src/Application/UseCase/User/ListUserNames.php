<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Domain\Port\UserRepository;

/** Alimenta el <select> de usuarios del gestor. */
final class ListUserNames
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @return array<int, array{id_usuario: int, username: string}>
     */
    public function execute(): array
    {
        return array_map(
            static fn (array $row) => [
                'id_usuario' => $row['id'],
                'username'   => $row['username'],
            ],
            $this->users->listNames()
        );
    }
}
