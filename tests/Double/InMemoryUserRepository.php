<?php

declare(strict_types=1);

namespace Tests\Double;

use App\Domain\Entity\User;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;

/** Repositorio en memoria: prueba los casos de uso sin base de datos. */
final class InMemoryUserRepository implements UserRepository
{
    /** @var User[] */
    private array $users = [];
    private int $nextId = 1;

    /** Si se define, add() lanza esta excepción (simula el índice único). */
    public ?\Throwable $failOnAdd = null;

    public function seed(User $user): User
    {
        return $this->add($user);
    }

    public function findById(UserId $id): ?User
    {
        foreach ($this->users as $user) {
            if ($user->id() !== null && $user->id()->equals($id)) {
                return $user;
            }
        }
        return null;
    }

    public function findByUsername(Username $username): ?User
    {
        foreach ($this->users as $user) {
            if ($user->username()->equals($username)) {
                return $user;
            }
        }
        return null;
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }
        return null;
    }

    public function findByRecoverySelector(string $selector): ?User
    {
        foreach ($this->users as $user) {
            $token = $user->recoveryToken();
            if ($token !== null && $token->selector() === $selector) {
                return $user;
            }
        }
        return null;
    }

    public function existsWithUsername(Username $username, ?UserId $excluding = null): bool
    {
        $found = $this->findByUsername($username);

        return $found !== null && !$this->isExcluded($found, $excluding);
    }

    public function existsWithEmail(Email $email, ?UserId $excluding = null): bool
    {
        $found = $this->findByEmail($email);

        return $found !== null && !$this->isExcluded($found, $excluding);
    }

    private function isExcluded(User $user, ?UserId $excluding): bool
    {
        return $excluding !== null
            && $user->id() !== null
            && $user->id()->equals($excluding);
    }

    public function add(User $user): User
    {
        if ($this->failOnAdd !== null) {
            throw $this->failOnAdd;
        }

        $stored = new User(
            UserId::fromInt($this->nextId++),
            $user->username(),
            $user->email(),
            $user->password(),
            $user->role(),
            $user->status(),
            $user->registeredAt(),
            $user->imageUrl(),
            $user->recoveryToken()
        );

        $this->users[] = $stored;

        return $stored;
    }

    public function save(User $user): void
    {
        foreach ($this->users as $i => $existing) {
            if ($existing->id() !== null && $user->id() !== null && $existing->id()->equals($user->id())) {
                $this->users[$i] = $user;
                return;
            }
        }
    }

    public function findAll(?UserId $id = null, ?bool $active = null): array
    {
        return array_values(array_filter($this->users, static function (User $u) use ($id, $active) {
            if ($id !== null && ($u->id() === null || !$u->id()->equals($id))) {
                return false;
            }
            if ($active !== null && $u->isActive() !== $active) {
                return false;
            }
            return true;
        }));
    }

    public function listNames(): array
    {
        return array_map(
            static fn (User $u) => [
                'id'       => $u->id() !== null ? $u->id()->value() : 0,
                'username' => $u->username()->value(),
            ],
            $this->users
        );
    }

    public function countByRole(int $roleId): int
    {
        $count = 0;
        foreach ($this->users as $user) {
            if ($user->role()->id() === $roleId) {
                $count++;
            }
        }
        return $count;
    }

    public function count(): int
    {
        return count($this->users);
    }
}
