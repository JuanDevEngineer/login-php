<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Dto\AuthenticatedUser;
use App\Application\Dto\UploadedImage;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Port\ImageStorage;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\UserId;

final class ChangeProfileImage
{
    private UserRepository $users;
    private ImageStorage $storage;

    public function __construct(UserRepository $users, ImageStorage $storage)
    {
        $this->users   = $users;
        $this->storage = $storage;
    }

    /**
     * @throws AccessDeniedException|UserNotFoundException
     */
    public function execute(AuthenticatedUser $actor, string $rawTargetId, UploadedImage $image): string
    {
        $targetId = UserId::fromMixed($rawTargetId);

        // Solo el dueño de la cuenta o un admin pueden cambiar la foto.
        $isSelf = $actor->id === $targetId->value();
        if (!$isSelf && !$actor->isAdmin()) {
            throw AccessDeniedException::create();
        }

        $user = $this->users->findById($targetId);
        if ($user === null) {
            throw UserNotFoundException::withId($targetId->value());
        }

        $url = $this->storage->store($image);

        $user->changeProfileImage($url);
        $this->users->save($user);

        return $url;
    }
}
