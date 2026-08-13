<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Port\RoleRepository;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;

final class UpdateUser
{
    private UserRepository $users;
    private RoleRepository $roles;

    public function __construct(UserRepository $users, RoleRepository $roles)
    {
        $this->users = $users;
        $this->roles = $roles;
    }

    /**
     * @throws UserNotFoundException|UserAlreadyExistsException
     */
    public function execute(string $rawId, string $rawUsername, string $rawEmail, string $rawRoleId): void
    {
        $id       = UserId::fromMixed($rawId);
        $username = Username::fromString($rawUsername);
        $email    = Email::fromString($rawEmail);

        $user = $this->users->findById($id);
        if ($user === null) {
            throw UserNotFoundException::withId($id->value());
        }

        // La unicidad se comprueba excluyendo al propio usuario, si no editar
        // sin cambiar el correo fallaría contra sí mismo.
        if ($this->users->existsWithUsername($username, $id)) {
            throw UserAlreadyExistsException::withUsername($username->value());
        }
        if ($this->users->existsWithEmail($email, $id)) {
            throw UserAlreadyExistsException::withEmail($email->value());
        }

        $role = $this->roles->findById((int) $rawRoleId);
        if ($role === null) {
            throw new \App\Domain\Exception\InvalidArgumentException('El rol indicado no existe.');
        }

        $user->changeProfile($username, $email, $role);
        $this->users->save($user);
    }
}
