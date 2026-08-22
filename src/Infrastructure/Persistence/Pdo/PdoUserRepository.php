<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pdo;

use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\Port\UserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\RecoveryToken;
use App\Domain\ValueObject\RoleName;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;
use App\Domain\ValueObject\UserStatus;

/**
 * ADAPTADOR MySQL del puerto UserRepository.
 *
 * Todas las consultas usan sentencias preparadas con parámetros ligados. No hay
 * un solo punto donde una variable se concatene dentro del SQL: ni siquiera los
 * filtros opcionales, que se arman con placeholders condicionales.
 */
final class PdoUserRepository implements UserRepository
{
    private const SELECT_BASE = '
        SELECT
            u.id_usuario,
            u.username,
            u.email,
            u.password,
            u.rol_id,
            u.registro,
            u.recover,
            u.estado,
            u.imagen_url,
            r.nombre AS rol_nombre,
            r.es_sistema AS rol_es_sistema
        FROM usuario u
        INNER JOIN rol r ON r.id_rol = u.rol_id
    ';

    private PdoConnection $connection;

    public function __construct(PdoConnection $connection)
    {
        $this->connection = $connection;
    }

    // ------------------------------------------------------------- búsquedas

    public function findById(UserId $id): ?User
    {
        $stmt = $this->connection->pdo()->prepare(
            self::SELECT_BASE . ' WHERE u.id_usuario = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $id->value(), \PDO::PARAM_INT);
        $stmt->execute();

        return $this->hydrateOrNull($stmt->fetch());
    }

    public function findByUsername(Username $username): ?User
    {
        // Comparación exacta y binaria. El código original usaba
        // "LIKE BINARY '%valor%'", que permitía entrar con un fragmento
        // del nombre de otro usuario.
        $stmt = $this->connection->pdo()->prepare(
            self::SELECT_BASE . ' WHERE u.username = BINARY :username LIMIT 1'
        );
        $stmt->bindValue(':username', $username->value(), \PDO::PARAM_STR);
        $stmt->execute();

        return $this->hydrateOrNull($stmt->fetch());
    }

    public function findByEmail(Email $email): ?User
    {
        $stmt = $this->connection->pdo()->prepare(
            self::SELECT_BASE . ' WHERE u.email = :email LIMIT 1'
        );
        $stmt->bindValue(':email', $email->value(), \PDO::PARAM_STR);
        $stmt->execute();

        return $this->hydrateOrNull($stmt->fetch());
    }

    public function findByRecoverySelector(string $selector): ?User
    {
        // El selector es el primer segmento de "selector:hash:expira", así que
        // buscamos por prefijo exacto con un placeholder — sin LIKE ni concatenación.
        $stmt = $this->connection->pdo()->prepare(
            self::SELECT_BASE . '
             WHERE u.recover IS NOT NULL
               AND SUBSTRING_INDEX(u.recover, \':\', 1) = :selector
             LIMIT 1'
        );
        $stmt->bindValue(':selector', $selector, \PDO::PARAM_STR);
        $stmt->execute();

        return $this->hydrateOrNull($stmt->fetch());
    }

    public function existsWithUsername(Username $username, ?UserId $excluding = null): bool
    {
        return $this->exists('username', $username->value(), $excluding);
    }

    public function existsWithEmail(Email $email, ?UserId $excluding = null): bool
    {
        return $this->exists('email', $email->value(), $excluding);
    }

    /**
     * @param string $column nombre de columna validado contra una whitelist,
     *                       nunca un valor arbitrario del usuario
     */
    private function exists(string $column, string $value, ?UserId $excluding): bool
    {
        $allowed = ['username', 'email'];
        if (!in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException('Columna no permitida: ' . $column);
        }

        $sql = sprintf('SELECT 1 FROM usuario WHERE %s = :value', $column);
        if ($excluding !== null) {
            $sql .= ' AND id_usuario <> :excluded';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->connection->pdo()->prepare($sql);
        $stmt->bindValue(':value', $value, \PDO::PARAM_STR);
        if ($excluding !== null) {
            $stmt->bindValue(':excluded', $excluding->value(), \PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function findAll(?UserId $id = null, ?bool $active = null): array
    {
        // Los filtros opcionales se acumulan como placeholders; los valores se
        // ligan después. El SQL nunca contiene datos del usuario.
        $conditions = [];
        $bindings   = [];

        if ($id !== null) {
            $conditions[]      = 'u.id_usuario = :id';
            $bindings[':id']   = [$id->value(), \PDO::PARAM_INT];
        }
        if ($active !== null) {
            $conditions[]        = 'u.estado = :estado';
            $bindings[':estado'] = [$active ? 1 : 0, \PDO::PARAM_INT];
        }

        $sql = self::SELECT_BASE;
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY u.id_usuario';

        $stmt = $this->connection->pdo()->prepare($sql);
        foreach ($bindings as $placeholder => [$value, $type]) {
            $stmt->bindValue($placeholder, $value, $type);
        }
        $stmt->execute();

        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function listNames(): array
    {
        $stmt = $this->connection->pdo()->query(
            'SELECT id_usuario, username FROM usuario ORDER BY username'
        );

        $rows = $stmt !== false ? $stmt->fetchAll() : [];

        return array_map(
            static fn (array $row) => [
                'id'       => (int) $row['id_usuario'],
                'username' => (string) $row['username'],
            ],
            $rows
        );
    }

    public function countByRole(int $roleId): int
    {
        $stmt = $this->connection->pdo()->prepare(
            'SELECT COUNT(*) FROM usuario WHERE rol_id = :rol_id'
        );
        $stmt->bindValue(':rol_id', $roleId, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    // ------------------------------------------------------------- escrituras

    public function add(User $user): User
    {
        $stmt = $this->connection->pdo()->prepare(
            'INSERT INTO usuario (username, email, password, rol_id, registro, estado, imagen_url)
             VALUES (:username, :email, :password, :rol_id, :registro, :estado, :imagen_url)'
        );

        $stmt->bindValue(':username', $user->username()->value(), \PDO::PARAM_STR);
        $stmt->bindValue(':email', $user->email()->value(), \PDO::PARAM_STR);
        $stmt->bindValue(':password', $user->password()->value(), \PDO::PARAM_STR);
        $stmt->bindValue(':rol_id', $user->role()->id() ?? 0, \PDO::PARAM_INT);
        $stmt->bindValue(
            ':registro',
            $user->registeredAt() !== null ? $user->registeredAt()->format('Y-m-d') : date('Y-m-d'),
            \PDO::PARAM_STR
        );
        $stmt->bindValue(':estado', $user->status()->toInt(), \PDO::PARAM_INT);
        $stmt->bindValue(
            ':imagen_url',
            $user->avatar(),
            $user->avatar() === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR
        );

        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            // Traducimos el error del motor a lenguaje de dominio acá, que es
            // el borde del sistema. Así el caso de uso nunca ve una PDOException
            // y no necesita saber qué es el SQLSTATE 23000.
            throw $this->translateWriteError($e, $user);
        }

        $id = (int) $this->connection->pdo()->lastInsertId();

        return new User(
            UserId::fromInt($id),
            $user->username(),
            $user->email(),
            $user->password(),
            $user->role(),
            $user->status(),
            $user->registeredAt(),
            $user->avatar(),
            $user->recoveryToken()
        );
    }

    public function save(User $user): void
    {
        if ($user->id() === null) {
            throw new \LogicException('No se puede actualizar un usuario sin id.');
        }

        $stmt = $this->connection->pdo()->prepare(
            'UPDATE usuario SET
                username   = :username,
                email      = :email,
                password   = :password,
                rol_id     = :rol_id,
                estado     = :estado,
                imagen_url = :imagen_url,
                recover    = :recover
             WHERE id_usuario = :id'
        );

        $token = $user->recoveryToken();

        $stmt->bindValue(':username', $user->username()->value(), \PDO::PARAM_STR);
        $stmt->bindValue(':email', $user->email()->value(), \PDO::PARAM_STR);
        $stmt->bindValue(':password', $user->password()->value(), \PDO::PARAM_STR);
        $stmt->bindValue(':rol_id', $user->role()->id() ?? 0, \PDO::PARAM_INT);
        $stmt->bindValue(':estado', $user->status()->toInt(), \PDO::PARAM_INT);
        $stmt->bindValue(
            ':imagen_url',
            $user->avatar(),
            $user->avatar() === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR
        );
        $stmt->bindValue(
            ':recover',
            $token !== null ? $token->toStorage() : null,
            $token !== null ? \PDO::PARAM_STR : \PDO::PARAM_NULL
        );
        $stmt->bindValue(':id', $user->id()->value(), \PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            throw $this->translateWriteError($e, $user);
        }
    }

    /**
     * Convierte una violación de índice único en la excepción de dominio
     * correspondiente. Cualquier otro error del motor se deja pasar tal cual:
     * no es un problema de negocio y debe llegar al log como error 500.
     */
    private function translateWriteError(\PDOException $e, User $user): \Throwable
    {
        $isDuplicate = $e->getCode() === '23000'
            || (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062);

        if (!$isDuplicate) {
            return $e;
        }

        // El mensaje de MySQL nombra el índice violado
        // ("Duplicate entry 'x' for key 'usuario_email_unique'"). Buscamos el
        // nombre del índice y no la palabra "email" suelta: un usuario llamado
        // "miemail" haría que la palabra apareciera en el mensaje de un
        // duplicado de username y atribuiríamos el error al campo equivocado.
        $message = $e->getMessage();

        if (stripos($message, 'usuario_email_unique') !== false) {
            return UserAlreadyExistsException::withEmail($user->email()->value());
        }

        if (stripos($message, 'usuario_username_unique') !== false) {
            return UserAlreadyExistsException::withUsername($user->username()->value());
        }

        // Índice desconocido: reportamos por usuario, que es el caso más común.
        return UserAlreadyExistsException::withUsername($user->username()->value());
    }

    // ------------------------------------------------------------- hidratación

    /** @param array<string, mixed>|false $row */
    private function hydrateOrNull($row): ?User
    {
        return $row === false ? null : $this->hydrate($row);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): User
    {
        $registeredAt = null;
        if (!empty($row['registro'])) {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $row['registro']);
            $registeredAt = $parsed === false ? null : $parsed;
        }

        $recoveryToken = null;
        if (!empty($row['recover'])) {
            try {
                $recoveryToken = RecoveryToken::fromStorage((string) $row['recover']);
            } catch (\Throwable $e) {
                // Token con formato antiguo o corrupto: se ignora y se tratará
                // como inexistente, forzando a pedir uno nuevo.
                $recoveryToken = null;
            }
        }

        return new User(
            UserId::fromInt((int) $row['id_usuario']),
            Username::fromString((string) $row['username']),
            Email::fromString((string) $row['email']),
            HashedPassword::fromHash((string) $row['password']),
            new Role(
                (int) $row['rol_id'],
                RoleName::fromString((string) $row['rol_nombre']),
                (bool) ($row['rol_es_sistema'] ?? false)
            ),
            UserStatus::fromInt((int) ($row['estado'] ?? 1)),
            $registeredAt,
            $row['imagen_url'] !== null ? (string) $row['imagen_url'] : null,
            $recoveryToken
        );
    }
}
