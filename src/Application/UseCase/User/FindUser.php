<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Dto\UserView;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\UserId;

final class FindUser
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @return array<string, mixed>
     * @throws UserNotFoundException
     */
    public function execute(string $rawId): array
    {
        $id   = UserId::fromMixed($rawId);
        $user = $this->users->findById($id);

        if ($user === null) {
            throw UserNotFoundException::withId($id->value());
        }

        return UserView::fromEntity($user)->toArray();
    }
}
