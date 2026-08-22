<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Conjunto de permisos de un rol.
 *
 * Inmutable: grant() y revoke() devuelven un conjunto nuevo en vez de mutar el
 * actual. Así un permiso no puede cambiar por debajo de quien ya lo consultó.
 */
final class PermissionSet
{
    /** @var array<string, Permission> indexado por código, para búsqueda O(1) */
    private array $permissions;

    /** @param list<Permission> $permissions */
    private function __construct(array $permissions)
    {
        $this->permissions = [];

        foreach ($permissions as $permission) {
            $this->permissions[$permission->value] = $permission;
        }
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /** @param list<Permission> $permissions */
    public static function of(array $permissions): self
    {
        return new self($permissions);
    }

    /** Todos los permisos del catálogo. */
    public static function all(): self
    {
        return new self(Permission::cases());
    }

    /**
     * Rehidrata desde los códigos guardados en base de datos.
     *
     * Los códigos que ya no existen en el enum se descartan en silencio: son
     * restos de una versión anterior y no deben impedir cargar el rol.
     *
     * @param list<string> $codes
     */
    public static function fromCodes(array $codes): self
    {
        $permissions = [];

        foreach ($codes as $code) {
            $permission = Permission::tryFromCode($code);
            if ($permission !== null) {
                $permissions[] = $permission;
            }
        }

        return new self($permissions);
    }

    public function has(Permission $permission): bool
    {
        return isset($this->permissions[$permission->value]);
    }

    public function grant(Permission $permission): self
    {
        $next = array_values($this->permissions);
        $next[] = $permission;

        return new self($next);
    }

    public function revoke(Permission $permission): self
    {
        $next = array_filter(
            array_values($this->permissions),
            static fn (Permission $p): bool => $p !== $permission
        );

        return new self(array_values($next));
    }

    public function isEmpty(): bool
    {
        return $this->permissions === [];
    }

    public function count(): int
    {
        return count($this->permissions);
    }

    /** @return list<string> códigos, en el orden del catálogo */
    public function toCodes(): array
    {
        $codes = [];

        foreach (Permission::cases() as $permission) {
            if ($this->has($permission)) {
                $codes[] = $permission->value;
            }
        }

        return $codes;
    }

    /** @return list<Permission> */
    public function toArray(): array
    {
        return array_values($this->permissions);
    }
}
