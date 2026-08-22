<?php

declare(strict_types=1);

namespace App\Application\UseCase\Permission;

use App\Application\Dto\AuthenticatedUser;
use App\Domain\Entity\Role;
use App\Domain\Port\RoleRepository;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\Permission;
use App\Domain\ValueObject\UserId;

/**
 * ¿El usuario en sesión tiene este permiso?
 *
 * Lee el rol DESDE LA BASE en cada petición, no de la sesión. Cachear los
 * permisos al iniciar sesión sería más barato, pero significaría que quitarle
 * un permiso a alguien conectado no surte efecto hasta que cierre sesión: un
 * agujero real. El coste es una consulta por petición, con caché dentro del
 * mismo request.
 */
final class UserCan
{
    /** @var array<int, Role|null> caché por request, indexada por id de usuario */
    private array $roleCache = [];

    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
    ) {
    }

    public function execute(?AuthenticatedUser $actor, Permission $permission): bool
    {
        if ($actor === null) {
            return false;
        }

        $role = $this->roleOf($actor);

        return $role !== null && $role->can($permission);
    }

    /** Todos los permisos efectivos, para filtrar el menú sin N consultas. */
    public function all(?AuthenticatedUser $actor): \App\Domain\ValueObject\PermissionSet
    {
        if ($actor === null) {
            return \App\Domain\ValueObject\PermissionSet::empty();
        }

        $role = $this->roleOf($actor);
        if ($role === null) {
            return \App\Domain\ValueObject\PermissionSet::empty();
        }

        // ROL_ADMIN puede todo: devolvemos el catálogo entero para que la
        // interfaz no tenga que conocer la excepción.
        return $role->isAdmin()
            ? \App\Domain\ValueObject\PermissionSet::all()
            : $role->permissions();
    }

    private function roleOf(AuthenticatedUser $actor): ?Role
    {
        if (array_key_exists($actor->id, $this->roleCache)) {
            return $this->roleCache[$actor->id];
        }

        $user = $this->users->findById(UserId::fromInt($actor->id));

        // Releemos el rol por id: el que viaja en la sesión es solo un nombre y
        // pudo cambiarse desde el gestor mientras la sesión seguía abierta.
        $role = $user !== null && $user->role()->id() !== null
            ? $this->roles->findById($user->role()->id())
            : null;

        return $this->roleCache[$actor->id] = $role;
    }
}
