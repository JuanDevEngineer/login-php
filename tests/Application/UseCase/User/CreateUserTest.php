<?php

declare(strict_types=1);

namespace Tests\Application\UseCase\User;

use App\Application\Dto\NewUserData;
use App\Application\Dto\UploadedImage;
use App\Application\UseCase\User\CreateUser;
use App\Domain\Exception\InvalidArgumentException;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\ValueObject\RoleName;
use App\Infrastructure\Security\BcryptPasswordHasher;
use PHPUnit\Framework\TestCase;
use Tests\Double\FixedClock;
use Tests\Double\InMemoryRoleRepository;
use Tests\Double\InMemoryUserRepository;
use Tests\Double\SpyImageStorage;

/**
 * Estos tests corren sin base de datos, sin SMTP y sin sistema de archivos:
 * es exactamente lo que habilita tener puertos en vez de dependencias duras.
 */
final class CreateUserTest extends TestCase
{
    private InMemoryUserRepository $users;
    private SpyImageStorage $storage;
    private CreateUser $useCase;

    protected function setUp(): void
    {
        $this->users   = new InMemoryUserRepository();
        $this->storage = new SpyImageStorage();

        $this->useCase = new CreateUser(
            $this->users,
            new InMemoryRoleRepository(),
            new BcryptPasswordHasher(),
            $this->storage,
            new FixedClock()
        );
    }

    private function data(array $overrides = []): NewUserData
    {
        $d = array_merge([
            'username' => 'juanjo',
            'email'    => 'juanjo@example.com',
            'password' => 'contrasena-larga',
            'confirm'  => 'contrasena-larga',
            'rol'      => '2',
            'active'   => true,
            'image'    => null,
        ], $overrides);

        return new NewUserData(
            $d['username'],
            $d['email'],
            $d['password'],
            $d['confirm'],
            $d['rol'],
            $d['active'],
            $d['image']
        );
    }

    public function testCreaUnUsuarioConRolYEstadoIndicados(): void
    {
        $result = $this->useCase->execute($this->data(['rol' => '1', 'active' => false]));

        self::assertSame('juanjo', $result['username']);
        self::assertSame(RoleName::ADMIN, $result['rol_usuario']);
        self::assertSame(0, $result['estado']);
        self::assertSame(1, $this->users->count());
    }

    public function testLaContrasenaSeGuardaHasheada(): void
    {
        $this->useCase->execute($this->data());

        $user = $this->users->findAll()[0];
        $hash = $user->password()->value();

        self::assertNotSame('contrasena-larga', $hash);
        self::assertStringStartsWith('$2y$', $hash);
        self::assertTrue(password_verify('contrasena-larga', $hash));
    }

    public function testRechazaContrasenasQueNoCoinciden(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->useCase->execute($this->data(['confirm' => 'otra-cosa-distinta']));
    }

    public function testRechazaContrasenaCorta(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->useCase->execute($this->data(['password' => 'corta', 'confirm' => 'corta']));
    }

    public function testRechazaUsuarioDuplicado(): void
    {
        $this->useCase->execute($this->data());

        $this->expectException(UserAlreadyExistsException::class);
        $this->useCase->execute($this->data(['email' => 'otro@example.com']));
    }

    public function testRechazaCorreoDuplicado(): void
    {
        $this->useCase->execute($this->data());

        $this->expectException(UserAlreadyExistsException::class);
        $this->useCase->execute($this->data(['username' => 'otro']));
    }

    public function testRechazaRolInexistente(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->useCase->execute($this->data(['rol' => '99']));
    }

    public function testGuardaLaImagenYLaAsociaAlUsuario(): void
    {
        $result = $this->useCase->execute($this->data([
            'image' => new UploadedImage('/tmp/falso.jpg', 1024, 'foto.jpg'),
        ]));

        self::assertCount(1, $this->storage->stored);
        self::assertSame($this->storage->stored[0], $result['avatar']);
        // El archivo se queda: lo referencia el usuario recién creado.
        self::assertSame([], $this->storage->deleted);
    }

    /**
     * La imagen no debe tocarse si la validación va a fallar: si no, cada
     * intento fallido dejaría un archivo basura en disco.
     */
    public function testNoGuardaLaImagenSiLaValidacionFalla(): void
    {
        try {
            $this->useCase->execute($this->data([
                'confirm' => 'no-coincide-nada',
                'image'   => new UploadedImage('/tmp/falso.jpg', 1024, 'foto.jpg'),
            ]));
            self::fail('Se esperaba una excepción.');
        } catch (InvalidArgumentException $e) {
            // esperado
        }

        self::assertSame([], $this->storage->stored, 'No debió subirse ninguna imagen.');
    }

    /**
     * Si el INSERT falla por una carrera con el índice único, la imagen ya
     * subida tiene que borrarse para no quedar huérfana.
     */
    public function testBorraLaImagenSiFallaElInsert(): void
    {
        $this->users->failOnAdd = UserAlreadyExistsException::withUsername('juanjo');

        try {
            $this->useCase->execute($this->data([
                'image' => new UploadedImage('/tmp/falso.jpg', 1024, 'foto.jpg'),
            ]));
            self::fail('Se esperaba una excepción.');
        } catch (UserAlreadyExistsException $e) {
            // esperado
        }

        self::assertCount(1, $this->storage->stored);
        self::assertCount(1, $this->storage->deleted);
        self::assertSame([], $this->storage->orphans(), 'Quedó una imagen huérfana.');
    }

    public function testCreaSinImagenCuandoNoSeEnvia(): void
    {
        $result = $this->useCase->execute($this->data());

        self::assertNull($result['avatar']);
        self::assertSame([], $this->storage->stored);
    }
}
