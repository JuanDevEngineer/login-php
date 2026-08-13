<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Domain\Exception\UserNotFoundException;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\UserId;

final class ToggleUserStatus
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * El estado nuevo lo decide la entidad a partir del estado real en base de
     * datos, no del valor que mandó el navegador.
     *
     * @throws UserNotFoundException
     */
    public function execute(string $rawId): int
    {
        $id   = UserId::fromMixed($rawId);
        $user = $this->users->findById($id);

        if ($user === null) {
            throw UserNotFoundException::withId($id->value());
        }

        $user->toggleStatus();
        $this->users->save($user);

        return $user->status()->toInt();
    }
}
