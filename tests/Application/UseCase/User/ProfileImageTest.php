<?php

declare(strict_types=1);

namespace Tests\Application\UseCase\User;

use App\Application\Dto\AuthenticatedUser;
use App\Application\Dto\NewUserData;
use App\Application\Dto\UploadedImage;
use App\Application\UseCase\User\ChangeProfileImage;
use App\Application\UseCase\User\CreateUser;
use App\Application\UseCase\User\RemoveProfileImage;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\ValueObject\RoleName;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Security\BcryptPasswordHasher;
use PHPUnit\Framework\TestCase;
use Tests\Double\FixedClock;
use Tests\Double\InMemoryRoleRepository;
use Tests\Double\InMemoryUserRepository;
use Tests\Double\SpyImageStorage;

final class ProfileImageTest extends TestCase
{
    private InMemoryUserRepository $users;
    private SpyImageStorage $storage;
    private ChangeProfileImage $change;
    private RemoveProfileImage $remove;

    protected function setUp(): void
    {
        $this->users   = new InMemoryUserRepository();
        $this->storage = new SpyImageStorage();

        $this->change = new ChangeProfileImage($this->users, $this->storage);
        $this->remove = new RemoveProfileImage($this->users, $this->storage);
    }

    /** Crea un usuario y devuelve su DTO de sesión. */
    private function crearUsuario(string $username, int $roleId = 2): AuthenticatedUser
    {
        $useCase = new CreateUser(
            $this->users,
            new InMemoryRoleRepository(),
            new BcryptPasswordHasher(),
            new SpyImageStorage(), // storage aparte: no contamina el espía
            new FixedClock()
        );

        $data = $useCase->execute(new NewUserData(
            $username,
            $username . '@example.com',
            'contrasena-larga',
            'contrasena-larga',
            (string) $roleId
        ));

        return new AuthenticatedUser(
            $data['id_usuario'],
            $data['username'],
            $data['email'],
            $data['rol_usuario'],
            $data['avatar']
        );
    }

    private function imagen(): UploadedImage
    {
        return new UploadedImage('/tmp/falso.jpg', 2048, 'foto.jpg');
    }

    // ------------------------------------------------------------------ subir

    public function testGuardaSoloElNombreDelArchivoNoUnaUrl(): void
    {
        $actor = $this->crearUsuario('juanjo');

        $resultado = $this->change->execute($actor, (string) $actor->id, $this->imagen());

        // Este es el punto de todo el cambio: en base de datos no puede haber
        // un http:// ni una barra, porque entonces dependería de BASE_URL.
        self::assertStringNotContainsString('http', $resultado);
        self::assertStringNotContainsString('/', $resultado);

        $user = $this->users->findById(UserId::fromInt($actor->id));
        self::assertNotNull($user);
        self::assertSame($resultado, $user->avatar());
    }

    public function testBorraLaFotoAnteriorAlReemplazarla(): void
    {
        $actor = $this->crearUsuario('juanjo');

        $primera = $this->change->execute($actor, (string) $actor->id, $this->imagen());
        $segunda = $this->change->execute($actor, (string) $actor->id, $this->imagen());

        self::assertNotSame($primera, $segunda);
        self::assertContains($primera, $this->storage->deleted,
            'La foto vieja debe borrarse: si no, cada cambio deja un archivo muerto.');
        self::assertNotContains($segunda, $this->storage->deleted);
    }

    public function testUnUsuarioNoPuedeCambiarLaFotoDeOtro(): void
    {
        $juan  = $this->crearUsuario('juanjo');
        $otro  = $this->crearUsuario('carlos');

        $this->expectException(AccessDeniedException::class);
        $this->change->execute($juan, (string) $otro->id, $this->imagen());
    }

    public function testUnAdminSiPuedeCambiarLaFotoDeOtro(): void
    {
        $admin = $this->crearUsuario('jefa', 1);
        $otro  = $this->crearUsuario('carlos');

        self::assertSame(RoleName::ADMIN, $admin->role);

        $resultado = $this->change->execute($admin, (string) $otro->id, $this->imagen());

        self::assertNotSame('', $resultado);
    }

    public function testNoSubeNadaSiElActorNoTienePermiso(): void
    {
        $juan = $this->crearUsuario('juanjo');
        $otro = $this->crearUsuario('carlos');

        try {
            $this->change->execute($juan, (string) $otro->id, $this->imagen());
        } catch (AccessDeniedException $e) {
            // esperado
        }

        // El permiso se comprueba antes de tocar el disco.
        self::assertSame([], $this->storage->stored);
    }

    // ----------------------------------------------------------------- quitar

    public function testQuitaLaFotoYBorraElArchivo(): void
    {
        $actor    = $this->crearUsuario('juanjo');
        $guardada = $this->change->execute($actor, (string) $actor->id, $this->imagen());

        $this->remove->execute($actor, (string) $actor->id);

        $user = $this->users->findById(UserId::fromInt($actor->id));
        self::assertNotNull($user);
        self::assertNull($user->avatar());
        self::assertContains($guardada, $this->storage->deleted);
    }

    public function testQuitarSinFotoNoFalla(): void
    {
        $actor = $this->crearUsuario('juanjo');

        $this->remove->execute($actor, (string) $actor->id);

        self::assertSame([], $this->storage->deleted);
    }

    public function testUnUsuarioNoPuedeQuitarLaFotoDeOtro(): void
    {
        $juan = $this->crearUsuario('juanjo');
        $otro = $this->crearUsuario('carlos');

        $this->expectException(AccessDeniedException::class);
        $this->remove->execute($juan, (string) $otro->id);
    }
}
