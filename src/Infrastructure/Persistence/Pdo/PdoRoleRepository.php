<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pdo;

use App\Domain\Entity\Role;
use App\Domain\Exception\RoleAlreadyExistsException;
use App\Domain\Exception\RoleInUseException;
use App\Domain\Port\RoleRepository;
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

        return $this->cache[$id] = $this->hydrate($row);
    }

    public function findByName(RoleName $name): ?Role
    {
        $stmt = $this->connection->pdo()->prepare(
            self::SELECT_BASE . ' WHERE nombre = :nombre LIMIT 1'
        );
        $stmt->bindValue(':nombre', $name->value(), \PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function findAll(): array
    {
        $stmt = $this->connection->pdo()->query(self::SELECT_BASE . ' ORDER BY es_sistema DESC, nombre');

        return array_map([$this, 'hydrate'], $stmt !== false ? $stmt->fetchAll() : []);
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

        $stored = new Role($id, $role->name(), false);
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

    public function delete(Role $role): void
    {
        if ($role->id() === null) {
            throw new \LogicException('No se puede eliminar un rol sin id.');
        }

        // El `AND es_sistema = 0` es la última red: aunque alguien saltease la
        // validación del dominio, la sentencia no afectaría a un rol protegido.
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

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Role
    {
        return new Role(
            (int) $row['id_rol'],
            RoleName::fromString((string) $row['nombre']),
            (bool) ($row['es_sistema'] ?? false)
        );
    }
}
