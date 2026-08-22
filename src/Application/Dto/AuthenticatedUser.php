<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Entity\User;

/**
 * Vista de solo lectura del usuario en sesión. Es lo único que la capa de
 * presentación necesita saber; la entidad de dominio no cruza esa frontera.
 */
final class AuthenticatedUser
{
    public int $id;
    public string $username;
    public string $email;
    public string $role;
    public ?string $avatar;

    public function __construct(int $id, string $username, string $email, string $role, ?string $avatar)
    {
        $this->id       = $id;
        $this->username = $username;
        $this->email    = $email;
        $this->role     = $role;
        $this->avatar   = $avatar;
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->id() !== null ? $user->id()->value() : 0,
            $user->username()->value(),
            $user->email()->value(),
            $user->role()->name()->value(),
            $user->avatar()
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === \App\Domain\ValueObject\RoleName::ADMIN;
    }

    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'username'  => $this->username,
            'email'     => $this->email,
            'role'      => $this->role,
            'avatar'    => $this->avatar,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            (string) ($data['username'] ?? ''),
            (string) ($data['email'] ?? ''),
            (string) ($data['role'] ?? ''),
            $data['avatar'] ?? null
        );
    }
}
