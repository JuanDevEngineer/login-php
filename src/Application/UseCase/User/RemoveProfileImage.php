<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Dto\AuthenticatedUser;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\UserNotFoundException;
use App\Application\UseCase\Permission\UserCan;
use App\Domain\Port\ImageStorage;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\Permission;
use App\Domain\ValueObject\UserId;

/** Quita la foto de perfil y borra el archivo del disco. */
final class RemoveProfileImage
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ImageStorage $storage,
        private readonly UserCan $userCan,
    ) {
    }

    /**
     * @throws AccessDeniedException|UserNotFoundException
     */
    public function execute(AuthenticatedUser $actor, string $rawTargetId): void
    {
        $targetId = UserId::fromMixed($rawTargetId);

        // Misma regla que al subir: la propia siempre, la ajena con permiso.
        if ($actor->id !== $targetId->value()
            && !$this->userCan->execute($actor, Permission::UsuariosEditar)
        ) {
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
