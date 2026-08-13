<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;

/**
 * PUERTO de persistencia de usuarios.
 *
 * El dominio declara qué necesita; infraestructura decide cómo. Cambiar MySQL
 * por otra cosa es escribir otro adaptador, sin tocar casos de uso.
 */
interface UserRepository
{
    public function findById(UserId $id): ?User;

    public function findByUsername(Username $username): ?User;

    public function findByEmail(Email $email): ?User;

    /** Busca por el selector público del token de recuperación. */
    public function findByRecoverySelector(string $selector): ?User;

    public function existsWithUsername(Username $username, ?UserId $excluding = null): bool;

    public function existsWithEmail(Email $email, ?UserId $excluding = null): bool;

    /** Inserta y devuelve el usuario con su id asignado. */
    public function add(User $user): User;

    public function save(User $user): void;

    /**
     * Listado para el gestor, con filtros opcionales.
     *
     * @return User[]
     */
    public function findAll(?UserId $id = null, ?bool $active = null): array;

    /**
     * Proyección liviana para poblar el <select> del gestor.
     *
     * @return array<int, array{id: int, username: string}>
     */
    public function listNames(): array;

    /**
     * Cuántos usuarios tienen asignado un rol. Se usa para impedir que se
     * elimine un rol que todavía está en uso.
     */
    public function countByRole(int $roleId): int;
}
