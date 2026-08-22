<?php

declare(strict_types=1);

namespace Tests\Application\UseCase\Auth;

use App\Application\Dto\NewUserData;
use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\User\CreateUser;
use App\Application\UseCase\User\ToggleUserStatus;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Infrastructure\Security\BcryptPasswordHasher;
use PHPUnit\Framework\TestCase;
use Tests\Double\FixedClock;
use Tests\Double\InMemoryLoginLog;
use Tests\Double\InMemoryRoleRepository;
use Tests\Double\InMemoryUserRepository;
use Tests\Double\NullSession;
use Tests\Double\SpyImageStorage;

final class LoginRecordsAccessTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryLoginLog $logins;
    private FixedClock $clock;
    private LoginUser $login;

    private const PASSWORD = 'contrasena-larga';

    protected function setUp(): void
    {
        $this->users  = new InMemoryUserRepository();
        $this->logins = new InMemoryLoginLog();
        $this->clock  = new FixedClock('2026-03-15 10:30:00');

        $this->login = new LoginUser(
            $this->users,
            new BcryptPasswordHasher(),
            new NullSession(),
            $this->logins,
            $this->clock
        );
    }

    private function crearUsuario(string $username): int
    {
        $useCase = new CreateUser(
            $this->users,
            new InMemoryRoleRepository(),
            new BcryptPasswordHasher(),
            new SpyImageStorage(),
            $this->clock
        );

        $data = $useCase->execute(new NewUserData(
            $username,
            $username . '@example.com',
            self::PASSWORD,
            self::PASSWORD,
            '2'
        ));

        return $data['id_usuario'];
    }

    public function testRegistraElAccesoTrasUnLoginCorrecto(): void
    {
        $id = $this->crearUsuario('ana');

        $this->login->execute('ana', self::PASSWORD);

        self::assertCount(1, $this->logins->entries);
        self::assertSame($id, $this->logins->entries[0]['user']);
        self::assertSame(
            $this->clock->now()->format('Y-m-d H:i:s'),
            $this->logins->entries[0]['at']->format('Y-m-d H:i:s')
        );
    }

    public function testNoRegistraNadaSiLaContrasenaEsIncorrecta(): void
    {
        $this->crearUsuario('ana');

        $this->expectException(InvalidCredentialsException::class);

        try {
            $this->login->execute('ana', 'contrasena-equivocada');
        } finally {
            self::assertSame([], $this->logins->entries);
        }
    }

    public function testNoRegistraNadaSiElUsuarioNoExiste(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        try {
            $this->login->execute('fantasma', self::PASSWORD);
        } finally {
            self::assertSame([], $this->logins->entries);
        }
    }

    public function testNoRegistraNadaSiLaCuentaEstaDesactivada(): void
    {
        $id = $this->crearUsuario('ana');
        (new ToggleUserStatus($this->users))->execute((string) $id);

        $this->expectException(AccessDeniedException::class);

        try {
            $this->login->execute('ana', self::PASSWORD);
        } finally {
            self::assertSame([], $this->logins->entries);
        }
    }

    /**
     * La bitácora es una métrica, no parte del contrato de autenticación: si
     * la escritura falla, el usuario igual entra. Lo contrario significaría
     * que un problema en una tabla de estadísticas deja a todo el mundo fuera.
     */
    public function testUnFalloAlRegistrarNoImpideIniciarSesion(): void
    {
        $this->crearUsuario('ana');
        $this->logins->failOnRecord = new \RuntimeException('tabla caída');

        $usuario = $this->login->execute('ana', self::PASSWORD);

        self::assertSame('ana', $usuario->username);
    }
}
