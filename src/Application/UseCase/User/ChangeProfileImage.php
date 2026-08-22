<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Dto\AuthenticatedUser;
use App\Application\Dto\UploadedImage;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\UserNotFoundException;
use App\Application\UseCase\Permission\UserCan;
use App\Domain\Port\ImageStorage;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\Permission;
use App\Domain\ValueObject\UserId;

final class ChangeProfileImage
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
    public function execute(AuthenticatedUser $actor, string $rawTargetId, UploadedImage $image): string
    {
        $targetId = UserId::fromMixed($rawTargetId);

        // Sobre la foto propia siempre se puede. Sobre la de otro hace falta
        // el permiso de editar usuarios: antes se preguntaba por el rol, lo que
        // dejaba esta regla fuera del sistema de permisos.
        $isSelf = $actor->id === $targetId->value();
        if (!$isSelf && !$this->userCan->execute($actor, Permission::UsuariosEditar)) {
            throw AccessDeniedException::create();
        }

        $user = $this->users->findById($targetId);
        if ($user === null) {
            throw UserNotFoundException::withId($targetId->value());
        }

        $previous = $user->avatar();

        $filename = $this->storage->store($image);

        $user->changeAvatar($filename);
        $this->users->save($user);

        // La foto anterior ya no la referencia nadie: si no la borramos, cada
        // cambio de avatar deja un archivo muerto en assets/uploads.
        if ($previous !== null && $previous !== $filename) {
            $this->storage->delete($previous);
        }

        return $filename;
    }
}
