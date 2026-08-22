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
use App\Application\UseCase\User\RemoveProfileImage;
use App\Application\UseCase\User\ToggleUserStatus;
use App\Application\UseCase\User\UpdateUser;
use App\Application\UseCase\Auth\LoginUser;
use App\Domain\Exception\DomainException;
use App\Domain\Port\SessionStorage;
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

        $targetId = $request->input('id') !== '' ? $request->input('id') : (string) $this->user->id;

        try {
            $image = UploadedImage::fromPhpUpload($request->file('profile'));

            $filename = $this->useCase(ChangeProfileImage::class)->execute(
                $this->user,
                $targetId,
                $image
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        $this->refreshSessionAvatar($targetId, $filename);

        return $this->ok([
            'message'   => 'Foto actualizada.',
            'avatar'    => $filename,
            'avatarUrl' => $this->avatarUrl($filename),
        ]);
    }

    public function removeImage(Request $request): Response
    {
        if ($this->user === null) {
            return $this->fail('No autenticado.', 401);
        }

        $targetId = $request->input('id') !== '' ? $request->input('id') : (string) $this->user->id;

        try {
            $this->useCase(RemoveProfileImage::class)->execute($this->user, $targetId);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        $this->refreshSessionAvatar($targetId, null);

        return $this->ok([
            'message'   => 'Foto eliminada.',
            'avatar'    => null,
            'avatarUrl' => $this->avatarUrl(null),
        ]);
    }

    /**
     * Si el usuario cambió su propia foto, actualizamos la copia que vive en la
     * sesión para que el cambio se vea sin volver a iniciar sesión.
     */
    private function refreshSessionAvatar(string $targetId, ?string $filename): void
    {
        if ($this->user === null || $this->user->id !== (int) $targetId) {
            return;
        }

        $session = $this->container->get(SessionStorage::class);
        $data    = $session->get(LoginUser::SESSION_KEY, []);

        if (is_array($data)) {
            $data['avatar'] = $filename;
            $session->set(LoginUser::SESSION_KEY, $data);
        }
    }

    private function avatarUrl(?string $filename): string
    {
        if ($filename === null || $filename === '') {
            return $this->baseUrl() . '/assets/admin/dist/img/user2-160x160.jpg';
        }

        return $this->baseUrl() . '/assets/uploads/' . rawurlencode($filename);
    }
}
