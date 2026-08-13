<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Datos crudos del formulario de alta de usuario.
 *
 * Son strings sin validar tal como llegaron por HTTP: la validación real la
 * hacen los value objects dentro del caso de uso. Este DTO solo evita que
 * CreateUser reciba siete parámetros sueltos y que el controlador tenga que
 * conocer el orden exacto.
 */
final class NewUserData
{
    public string $username;
    public string $email;
    public string $password;
    public string $passwordConfirmation;
    public string $roleId;
    public bool $active;
    public ?UploadedImage $image;

    public function __construct(
        string $username,
        string $email,
        string $password,
        string $passwordConfirmation,
        string $roleId,
        bool $active = true,
        ?UploadedImage $image = null
    ) {
        $this->username             = $username;
        $this->email                = $email;
        $this->password             = $password;
        $this->passwordConfirmation = $passwordConfirmation;
        $this->roleId               = $roleId;
        $this->active               = $active;
        $this->image                = $image;
    }

    /** Evita que las contraseñas aparezcan en un var_dump o un stack trace. */
    public function __debugInfo(): array
    {
        return [
            'username' => $this->username,
            'email'    => $this->email,
            'password' => '***',
            'roleId'   => $this->roleId,
            'active'   => $this->active,
            'image'    => $this->image !== null ? 'sí' : 'no',
        ];
    }
}
