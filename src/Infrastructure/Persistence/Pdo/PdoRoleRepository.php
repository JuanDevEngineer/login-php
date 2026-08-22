<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pdo;

use App\Domain\Entity\Role;
use App\Domain\Exception\RoleAlreadyExistsException;
use App\Domain\Exception\RoleInUseException;
use App\Domain\Port\RoleRepository;
use App\Domain\ValueObject\PermissionSet;
use App\Domain\ValueObject\RoleName;

final class PdoRoleRepository implements RoleRepository
{
    private const SELECT_BASE = 'SELECT id_rol, nombre, es_sistema FROM rol';

    private PdoConnection $connection;

    /** @var array<int, Role> caché por request */
    private array $cache = [];

    public function __construct(PdoConnection $connection)
    {
        $this->connection = $connection;
    }

    // ------------------------------------------------------------- búsquedas

    public function findById(int $id): ?Role
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        $stmt = $this->connection->pdo()->prepare(
            self::SELECT_BASE . ' WHERE id_rol = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $permissions = $this->loadPermissions([$id]);

        return $this->cache[$id] = $this->hydrate($row, $permissions[$id] ?? []);
    }

    public function findByName(RoleName $name): ?Role
    {
        $stmt = $this->connection->pdo()->prepare(
            self::SELECT_BASE . ' WHERE nombre = :nombre LIMIT 1'
        );
        $stmt->bindValue(':nombre', $name->value(), \PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $id = (int) $row['id_rol'];
        $permissions = $this->loadPermissions([$id]);

        return $this->hydrate($row, $permissions[$id] ?? []);
    }

    public function findAll(): array
    {
        $stmt = $this->connection->pdo()->query(
            self::SELECT_BASE . ' ORDER BY es_sistema DESC, nombre'
        );

        $rows = $stmt !== false ? $stmt->fetchAll() : [];
        if ($rows === []) {
            return [];
        }

        // Carga en lote: una sola consulta para los permisos de todos los
        // roles. Pedirlos rol por rol sería el clásico N+1.
        $ids = array_map(static fn (array $row): int => (int) $row['id_rol'], $rows);
        $permissions = $this->loadPermissions($ids);

        return array_map(
            fn (array $row): Role => $this->hydrate($row, $permissions[(int) $row['id_rol']] ?? []),
            $rows
        );
    }

    public function existsWithName(RoleName $name, ?int $excluding = null): bool
    {
        $sql = 'SELECT 1 FROM rol WHERE nombre = :nombre';
        if ($excluding !== null) {
            $sql .= ' AND id_rol <> :excluded';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->connection->pdo()->prepare($sql);
        $stmt->bindValue(':nombre', $name->value(), \PDO::PARAM_STR);
        if ($excluding !== null) {
            $stmt->bindValue(':excluded', $excluding, \PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    // ------------------------------------------------------------- escrituras

    public function add(Role $role): Role
    {
        $stmt = $this->connection->pdo()->prepare(
            'INSERT INTO rol (nombre, es_sistema) VALUES (:nombre, 0)'
        );
        $stmt->bindValue(':nombre', $role->name()->value(), \PDO::PARAM_STR);

        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            throw $this->translateWriteError($e, $role);
        }

        $id = (int) $this->connection->pdo()->lastInsertId();

        $stored = new Role($id, $role->name(), false, $role->permissions());
        $this->cache[$id] = $stored;

        return $stored;
    }

    public function save(Role $role): void
    {
        if ($role->id() === null) {
            throw new \LogicException('No se puede actualizar un rol sin id.');
        }

        // `es_sistema` no se toca desde la aplicación: es una marca de
        // infraestructura que solo pone la migración. Si se pudiera escribir
        // desde acá, un admin podría desproteger ROL_ADMIN y luego borrarlo.
        $stmt = $this->connection->pdo()->prepare(
            'UPDATE rol SET nombre = :nombre WHERE id_rol = :id AND es_sistema = 0'
        );
        $stmt->bindValue(':nombre', $role->name()->value(), \PDO::PARAM_STR);
        $stmt->bindValue(':id', $role->id(), \PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            throw $this->translateWriteError($e, $role);
        }

        unset($this->cache[$role->id()]);
    }

    public function syncPermissions(Role $role): void
    {
        $id = $role->id();
        if ($id === null) {
            throw new \LogicException('No se pueden asignar permisos a un rol sin id.');
        }

        $pdo = $this->connection->pdo();

        // Borrar e insertar dentro de una transacción: si fallara a mitad, el
        // rol quedaría sin ningún permiso, que es peor que no haber tocado nada.
        $pdo->beginTransaction();

        try {
            $delete = $pdo->prepare('DELETE FROM rol_permiso WHERE rol_id = :rol_id');
            $delete->bindValue(':rol_id', $id, \PDO::PARAM_INT);
            $delete->execute();

            $codes = $role->permissions()->toCodes();

            if ($codes !== []) {
                $insert = $pdo->prepare(
                    'INSERT INTO rol_permiso (rol_id, permiso) VALUES (:rol_id, :permiso)'
                );

                foreach ($codes as $code) {
                    $insert->bindValue(':rol_id', $id, \PDO::PARAM_INT);
                    $insert->bindValue(':permiso', $code, \PDO::PARAM_STR);
                    $insert->execute();
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        unset($this->cache[$id]);
    }

    public function delete(Role $role): void
    {
        if ($role->id() === null) {
            throw new \LogicException('No se puede eliminar un rol sin id.');
        }

        // El `AND es_sistema = 0` es la última red: aunque alguien saltease la
        // validación del dominio, la sentencia no afectaría a un rol protegido.
        // Los permisos del pivote se van solos por la clave foránea en cascada.
        $stmt = $this->connection->pdo()->prepare(
            'DELETE FROM rol WHERE id_rol = :id AND es_sistema = 0'
        );
        $stmt->bindValue(':id', $role->id(), \PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            // La clave foránea de `usuario` rechaza el borrado si quedan
            // usuarios con ese rol. El caso de uso ya lo comprueba antes, pero
            // entre esa comprobación y este DELETE alguien pudo asignarlo.
            if ($this->isForeignKeyViolation($e)) {
                throw RoleInUseException::withUsers($role->name()->value(), 1);
            }
            throw $e;
        }

        unset($this->cache[$role->id()]);
    }

    // ------------------------------------------------------------- internos

    /**
     * Códigos de permiso de varios roles en una sola consulta.
     *
     * @param list<int> $roleIds
     * @return array<int, list<string>>
     */
    private function loadPermissions(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        // Placeholders posicionales generados a partir del NÚMERO de ids, no de
        // su contenido: el SQL nunca lleva un valor incrustado.
        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));

        $stmt = $this->connection->pdo()->prepare(
            "SELECT rol_id, permiso FROM rol_permiso WHERE rol_id IN ($placeholders)"
        );

        foreach (array_values($roleIds) as $i => $id) {
            $stmt->bindValue($i + 1, $id, \PDO::PARAM_INT);
        }
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['rol_id']][] = (string) $row['permiso'];
        }

        return $result;
    }

    private function translateWriteError(\PDOException $e, Role $role): \Throwable
    {
        $isDuplicate = $e->getCode() === '23000'
            || (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062);

        if ($isDuplicate) {
            return RoleAlreadyExistsException::withName($role->name()->value());
        }

        return $e;
    }

    private function isForeignKeyViolation(\PDOException $e): bool
    {
        return isset($e->errorInfo[1]) && in_array((int) $e->errorInfo[1], [1451, 1217], true);
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string>         $permissionCodes
     */
    private function hydrate(array $row, array $permissionCodes): Role
    {
        return new Role(
            (int) $row['id_rol'],
            RoleName::fromString((string) $row['nombre']),
            (bool) ($row['es_sistema'] ?? false),
            PermissionSet::fromCodes($permissionCodes)
        );
    }
}
