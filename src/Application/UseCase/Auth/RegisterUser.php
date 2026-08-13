<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Dto\AuthenticatedUser;
use App\Domain\Entity\User;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Port\Clock;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\RoleRepository;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PlainPassword;
use App\Domain\ValueObject\RoleName;
use App\Domain\ValueObject\Username;

final class RegisterUser
{
    private UserRepository $users;
    private RoleRepository $roles;
    private PasswordHasher $hasher;
    private Clock $clock;

    public function __construct(
        UserRepository $users,
        RoleRepository $roles,
        PasswordHasher $hasher,
        Clock $clock
    ) {
        $this->users  = $users;
        $this->roles  = $roles;
        $this->hasher = $hasher;
        $this->clock  = $clock;
    }

    /**
     * @throws UserAlreadyExistsException
     */
    public function execute(string $rawUsername, string $rawEmail, string $rawPassword): AuthenticatedUser
    {
        $username = Username::fromString($rawUsername);
        $email    = Email::fromString($rawEmail);
        $password = PlainPassword::fromString($rawPassword);

        if ($this->users->existsWithUsername($username)) {
            throw UserAlreadyExistsException::withUsername($username->value());
        }
        if ($this->users->existsWithEmail($email)) {
            throw UserAlreadyExistsException::withEmail($email->value());
        }

        $defaultRole = $this->roles->findByName(RoleName::user());
        if ($defaultRole === null) {
            throw new \RuntimeException('El rol por defecto ROL_USER no existe en la base de datos.');
        }

        $user = User::register(
            $username,
            $email,
            $this->hasher->hash($password),
            $defaultRole,
            $this->clock->now()
        );

        $user = $this->users->add($user);

        if ($user->id() === null) {
            throw UserNotFoundException::create();
        }

        return AuthenticatedUser::fromEntity($user);
    }
}
