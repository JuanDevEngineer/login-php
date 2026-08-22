<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Dto\AuthenticatedUser;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Port\ImageStorage;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\UserId;

/** Quita la foto de perfil y borra el archivo del disco. */
final class RemoveProfileImage
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
    public function execute(AuthenticatedUser $actor, string $rawTargetId): void
    {
        $targetId = UserId::fromMixed($rawTargetId);

        if ($actor->id !== $targetId->value() && !$actor->isAdmin()) {
            throw AccessDeniedException::create();
        }

        $user = $this->users->findById($targetId);
        if ($user === null) {
            throw UserNotFoundException::withId($targetId->value());
        }

        $current = $user->avatar();
        if ($current === null) {
            return; // Ya no tenía foto: nada que hacer.
        }

        // Primero la base de datos. Si el borrado del archivo fallara después,
        // lo peor que queda es un archivo huérfano; al revés tendríamos una
        // fila apuntando a un archivo inexistente, que es peor.
        $user->removeAvatar();
        $this->users->save($user);

        $this->storage->delete($current);
    }
}
