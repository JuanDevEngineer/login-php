<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\RecoveryToken;
use App\Domain\ValueObject\RoleName;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;
use App\Domain\ValueObject\UserStatus;

/**
 * Entidad raíz del agregado Usuario.
 *
 * No sabe nada de PDO, de $_SESSION ni de HTTP: solo expresa qué es un usuario
 * y qué se le puede hacer. Las reglas que dependen de otros usuarios (unicidad
 * de correo, por ejemplo) viven en los casos de uso, porque necesitan consultar
 * el repositorio.
 */
final class User
{
    private ?UserId $id;
    private Username $username;
    private Email $email;
    private HashedPassword $password;
    private Role $role;
    private UserStatus $status;
    private ?\DateTimeImmutable $registeredAt;
    private ?string $avatar;
    private ?RecoveryToken $recoveryToken;

    public function __construct(
        ?UserId $id,
        Username $username,
        Email $email,
        HashedPassword $password,
        Role $role,
        UserStatus $status,
        ?\DateTimeImmutable $registeredAt = null,
        ?string $avatar = null,
        ?RecoveryToken $recoveryToken = null
    ) {
        $this->id            = $id;
        $this->username      = $username;
        $this->email         = $email;
        $this->password      = $password;
        $this->role          = $role;
        $this->status        = $status;
        $this->registeredAt  = $registeredAt;
        $this->avatar        = $avatar;
        $this->recoveryToken = $recoveryToken;
    }

    /**
     * Auto-registro desde el formulario público: sin id todavía, siempre activo
     * y con el rol que decida quien llama (en la práctica, ROL_USER).
     */
    public static function register(
        Username $username,
        Email $email,
        HashedPassword $password,
        Role $role,
        \DateTimeImmutable $registeredAt
    ): self {
        return new self(
            null,
            $username,
            $email,
            $password,
            $role,
            UserStatus::active(),
            $registeredAt
        );
    }

    /**
     * Alta hecha por un administrador desde el panel.
     *
     * A diferencia de register(), acá el rol, el estado inicial y la foto se
     * deciden explícitamente: el admin puede crear otro admin, o dejar la
     * cuenta inactiva para alguien que todavía no debe poder entrar.
     */
    public static function createByAdmin(
        Username $username,
        Email $email,
        HashedPassword $password,
        Role $role,
        UserStatus $status,
        \DateTimeImmutable $registeredAt,
        ?string $avatar = null
    ): self {
        return new self(
            null,
            $username,
            $email,
            $password,
            $role,
            $status,
            $registeredAt,
            $avatar
        );
    }

    // ---------------------------------------------------------------- lecturas

    public function id(): ?UserId
    {
        return $this->id;
    }

    public function username(): Username
    {
        return $this->username;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function registeredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    /**
     * Nombre del archivo de la foto, no una URL. La URL la arma la vista, así
     * las fotos no se rompen si cambia BASE_URL o se mueve el proyecto.
     */
    public function avatar(): ?string
    {
        return $this->avatar;
    }

    public function recoveryToken(): ?RecoveryToken
    {
        return $this->recoveryToken;
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    // ----------------------------------------------------------- comportamiento

    public function changePassword(HashedPassword $password): void
    {
        $this->password = $password;
        // Cambiar la contraseña invalida cualquier enlace de recuperación
        // pendiente: el token es de un solo uso.
        $this->recoveryToken = null;
    }

    public function startPasswordRecovery(RecoveryToken $token): void
    {
        $this->recoveryToken = $token;
    }

    public function clearPasswordRecovery(): void
    {
        $this->recoveryToken = null;
    }

    public function changeProfile(Username $username, Email $email, Role $role): void
    {
        $this->username = $username;
        $this->email    = $email;
        $this->role     = $role;
    }

    public function changeAvatar(string $filename): void
    {
        $this->avatar = $filename;
    }

    public function removeAvatar(): void
    {
        $this->avatar = null;
    }

    public function toggleStatus(): void
    {
        $this->status = $this->status->toggled();
    }

    /** ¿Este usuario puede modificar el perfil de $target? */
    public function canManage(UserId $target): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->id !== null && $this->id->equals($target);
    }
}
