<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Dto\NewUserData;
use App\Application\Dto\UserView;
use App\Domain\Entity\User;
use App\Domain\Exception\InvalidArgumentException;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\Port\Clock;
use App\Domain\Port\ImageStorage;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\RoleRepository;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PlainPassword;
use App\Domain\ValueObject\Username;
use App\Domain\ValueObject\UserStatus;

/**
 * Alta de usuario hecha por un administrador desde el panel.
 *
 * Se diferencia de RegisterUser (auto-registro público) en que acá el rol, el
 * estado inicial y la foto los decide el admin. La contraseña la escribe él y
 * se hashea igual que en cualquier otro alta: nunca se guarda en claro.
 */
final class CreateUser
{
    private UserRepository $users;
    private RoleRepository $roles;
    private PasswordHasher $hasher;
    private ImageStorage $storage;
    private Clock $clock;

    public function __construct(
        UserRepository $users,
        RoleRepository $roles,
        PasswordHasher $hasher,
        ImageStorage $storage,
        Clock $clock
    ) {
        $this->users   = $users;
        $this->roles   = $roles;
        $this->hasher  = $hasher;
        $this->storage = $storage;
        $this->clock   = $clock;
    }

    /**
     * @return array<string, mixed> proyección del usuario creado
     *
     * @throws UserAlreadyExistsException|InvalidArgumentException
     */
    public function execute(NewUserData $data): array
    {
        // ---- 1. Validar todo ANTES de tocar el disco -----------------------
        // Si la imagen se guardara primero y después fallara la validación,
        // quedaría un archivo huérfano en assets/uploads por cada intento
        // fallido: un vector de llenado de disco trivial de explotar.
        $username = Username::fromString($data->username);
        $email    = Email::fromString($data->email);

        if ($data->password !== $data->passwordConfirmation) {
            throw new InvalidArgumentException('Las contraseñas no coinciden.');
        }
        $password = PlainPassword::fromString($data->password);

        if ($this->users->existsWithUsername($username)) {
            throw UserAlreadyExistsException::withUsername($username->value());
        }
        if ($this->users->existsWithEmail($email)) {
            throw UserAlreadyExistsException::withEmail($email->value());
        }

        $role = $this->roles->findById((int) $data->roleId);
        if ($role === null) {
            throw new InvalidArgumentException('El rol indicado no existe.');
        }

        // ---- 2. Recién ahora, guardar la imagen ----------------------------
        $imageUrl = null;
        if ($data->image !== null) {
            $imageUrl = $this->storage->store($data->image);
        }

        // ---- 3. Insertar ---------------------------------------------------
        try {
            $user = User::createByAdmin(
                $username,
                $email,
                $this->hasher->hash($password),
                $role,
                $data->active ? UserStatus::active() : UserStatus::inactive(),
                $this->clock->now(),
                $imageUrl
            );

            $user = $this->users->add($user);
        } catch (\Throwable $e) {
            // Entre la comprobación de unicidad y el INSERT otra petición pudo
            // tomar el mismo usuario o correo, y el índice único lo rechaza.
            // El repositorio traduce esa violación a UserAlreadyExistsException;
            // acá solo nos ocupamos de que la imagen ya subida no quede huérfana.
            if ($imageUrl !== null) {
                $this->storage->delete($imageUrl);
            }

            throw $e;
        }

        return UserView::fromEntity($user)->toArray();
    }
}
