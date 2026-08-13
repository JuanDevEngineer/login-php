<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Entity\User;

/** Proyección de un usuario para las respuestas JSON del gestor. */
final class UserView
{
    public int $id;
    public string $username;
    public string $email;
    public int $roleId;
    public string $roleName;
    public int $status;
    public string $statusLabel;
    public ?string $registeredAt;
    public ?string $imageUrl;

    public function __construct(
        int $id,
        string $username,
        string $email,
        int $roleId,
        string $roleName,
        int $status,
        string $statusLabel,
        ?string $registeredAt,
        ?string $imageUrl
    ) {
        $this->id           = $id;
        $this->username     = $username;
        $this->email        = $email;
        $this->roleId       = $roleId;
        $this->roleName     = $roleName;
        $this->status       = $status;
        $this->statusLabel  = $statusLabel;
        $this->registeredAt = $registeredAt;
        $this->imageUrl     = $imageUrl;
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->id() !== null ? $user->id()->value() : 0,
            $user->username()->value(),
            $user->email()->value(),
            // Role::id() es ?int porque un rol recién construido aún no lo
            // tiene; un usuario persistido siempre trae uno, pero el ?? 0 evita
            // un TypeError si alguna vez llega sin él.
            $user->role()->id() ?? 0,
            $user->role()->name()->value(),
            $user->status()->toInt(),
            $user->status()->label(),
            $user->registeredAt() !== null ? $user->registeredAt()->format('Y-m-d') : null,
            $user->imageUrl()
        );
    }

    /**
     * Claves en snake_case porque son las que ya consume la DataTable del
     * frontend (public/js/usuarios.js).
     */
    public function toArray(): array
    {
        return [
            'id_usuario' => $this->id,
            'username'   => $this->username,
            'email'      => $this->email,
            'rol_id'     => $this->roleId,
            'rol_usuario'=> $this->roleName,
            'estado'     => $this->status,
            'estado_txt' => $this->statusLabel,
            'registro'   => $this->registeredAt,
            'imagen_url' => $this->imageUrl,
        ];
    }
}
