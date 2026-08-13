<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Dto\UserView;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\UserId;

final class ListUsers
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(?string $rawId = null, ?string $rawStatus = null): array
    {
        $id = ($rawId !== null && $rawId !== '') ? UserId::fromMixed($rawId) : null;

        $active = null;
        if ($rawStatus !== null && $rawStatus !== '') {
            $active = ((int) $rawStatus) === 1;
        }

        return array_map(
            static fn ($user) => UserView::fromEntity($user)->toArray(),
            $this->users->findAll($id, $active)
        );
    }
}
