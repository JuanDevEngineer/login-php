<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Dto\NewUserData;
use App\Application\Dto\UploadedImage;
use App\Application\UseCase\User\ChangeProfileImage;
use App\Application\UseCase\User\CreateUser;
use App\Application\UseCase\User\FindUser;
use App\Application\UseCase\User\ListRoles;
use App\Application\UseCase\User\ListUserNames;
use App\Application\UseCase\User\ListUsers;
use App\Application\UseCase\User\ToggleUserStatus;
use App\Application\UseCase\User\UpdateUser;
use App\Domain\Exception\DomainException;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * Endpoints JSON del gestor. El control de acceso (admin / auth) lo aplica el
 * router según la tabla de rutas, así que acá no hay chequeos de rol repetidos.
 */
final class UserApiController extends AbstractController
{
    public function create(Request $request): Response
    {
        // La imagen es opcional: solo construimos el DTO de archivo si vino
        // algo y no fue un "no se seleccionó ninguno".
        $image = null;
        $upload = $request->file('profile');
        if ($upload !== null && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $image = UploadedImage::fromPhpUpload($upload);
        }

        $data = new NewUserData(
            $request->input('username'),
            $request->input('email'),
            $request->raw('password'),
            $request->raw('password_confirm'),
            $request->input('rol'),
            $request->input('estado', '1') === '1',
            $image
        );

        try {
            $user = $this->useCase(CreateUser::class)->execute($data);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok([
            'message' => 'Usuario creado.',
            'user'    => $user,
        ]);
    }

    public function list(Request $request): Response
    {
        $rows = $this->useCase(ListUsers::class)->execute(
            $request->input('id'),
            $request->input('estado')
        );

        // La DataTable espera el array pelado como dataSrc.
        return $this->json($rows);
    }

    public function find(Request $request): Response
    {
        try {
            $user = $this->useCase(FindUser::class)->execute($request->input('id'));
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), 404);
        }

        return $this->json($user);
    }

    public function names(Request $request): Response
    {
        return $this->json($this->useCase(ListUserNames::class)->execute());
    }

    public function roles(Request $request): Response
    {
        return $this->json($this->useCase(ListRoles::class)->execute());
    }

    public function update(Request $request): Response
    {
        try {
            $this->useCase(UpdateUser::class)->execute(
                $request->input('id'),
                $request->input('username'),
                $request->input('email'),
                $request->input('rol')
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok(['message' => 'Usuario actualizado.']);
    }

    public function toggleStatus(Request $request): Response
    {
        try {
            // El estado nuevo lo calcula el dominio a partir del valor real en
            // base de datos, no del que mandó el navegador.
            $status = $this->useCase(ToggleUserStatus::class)->execute($request->input('id'));
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), 404);
        }

        return $this->ok(['estado' => $status]);
    }

    public function uploadImage(Request $request): Response
    {
        if ($this->user === null) {
            return $this->fail('No autenticado.', 401);
        }

        try {
            $image = UploadedImage::fromPhpUpload($request->file('profile'));

            $url = $this->useCase(ChangeProfileImage::class)->execute(
                $this->user,
                $request->input('id') !== '' ? $request->input('id') : (string) $this->user->id,
                $image
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        // Si cambió su propia foto, refrescamos la copia en sesión para que la
        // vea de inmediato sin volver a iniciar sesión.
        if ($this->user->id === (int) ($request->input('id') ?: $this->user->id)) {
            $session = $this->container->get(\App\Domain\Port\SessionStorage::class);
            $data    = $session->get(\App\Application\UseCase\Auth\LoginUser::SESSION_KEY, []);
            if (is_array($data)) {
                $data['imageUrl'] = $url;
                $session->set(\App\Application\UseCase\Auth\LoginUser::SESSION_KEY, $data);
            }
        }

        return $this->ok([
            'message'  => 'Imagen actualizada.',
            'imageUrl' => $url,
        ]);
    }
}
