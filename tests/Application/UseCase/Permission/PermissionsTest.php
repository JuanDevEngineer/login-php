<?php

declare(strict_types=1);

namespace Tests\Application\UseCase\Permission;

use App\Application\Dto\AuthenticatedUser;
use App\Application\Dto\NewUserData;
use App\Application\UseCase\Permission\GetPermissionMatrix;
use App\Application\UseCase\Permission\SyncRolePermissions;
use App\Application\UseCase\Permission\UserCan;
use App\Application\UseCase\User\CreateUser;
use App\Domain\Exception\ProtectedRoleException;
use App\Domain\Exception\RoleNotFoundException;
use App\Domain\ValueObject\Permission;
use App\Domain\ValueObject\PermissionSet;
use App\Domain\ValueObject\RoleName;
use App\Infrastructure\Security\BcryptPasswordHasher;
use PHPUnit\Framework\TestCase;
use Tests\Double\FixedClock;
use Tests\Double\InMemoryRoleRepository;
use Tests\Double\InMemoryUserRepository;
use Tests\Double\SpyImageStorage;

final class PermissionsTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryRoleRepository $roles;
    private SyncRolePermissions $sync;

    private const ID_ADMIN = 1;
    private const ID_USER  = 2;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->roles = new InMemoryRoleRepository();
        $this->sync  = new SyncRolePermissions($this->roles);
    }

    private function userCan(): UserCan
    {
        // Instancia nueva: UserCan cachea el rol por request, y varios tests
        // necesitan ver el efecto de un cambio.
        return new UserCan($this->users, $this->roles);
    }

    private function crearUsuario(string $username, int $roleId): AuthenticatedUser
    {
        $useCase = new CreateUser(
            $this->users,
            $this->roles,
            new BcryptPasswordHasher(),
            new SpyImageStorage(),
            new FixedClock()
        );

        $d = $useCase->execute(new NewUserData(
            $username,
            $username . '@example.com',
            'contrasena-larga',
            'contrasena-larga',
            (string) $roleId
        ));

        return new AuthenticatedUser(
            $d['id_usuario'],
            $d['username'],
            $d['email'],
            $d['rol_usuario'],
            $d['avatar']
        );
    }

    // ------------------------------------------------------- PermissionSet

    public function testElConjuntoVacioNoTieneNada(): void
    {
        self::assertFalse(PermissionSet::empty()->has(Permission::UsuariosVer));
        self::assertTrue(PermissionSet::empty()->isEmpty());
    }

    public function testGrantYRevokeDevuelvenConjuntosNuevos(): void
    {
        $base = PermissionSet::empty();
        $con  = $base->grant(Permission::UsuariosVer);
        $sin  = $con->revoke(Permission::UsuariosVer);

        self::assertFalse($base->has(Permission::UsuariosVer), 'El original no debe mutar.');
        self::assertTrue($con->has(Permission::UsuariosVer));
        self::assertFalse($sin->has(Permission::UsuariosVer));
    }

    public function testDescartaCodigosQueYaNoExistenEnElEnum(): void
    {
        $set = PermissionSet::fromCodes(['usuarios.ver', 'permiso.fantasma.de.otra.version']);

        self::assertTrue($set->has(Permission::UsuariosVer));
        self::assertSame(['usuarios.ver'], $set->toCodes());
    }

    public function testNoDuplicaPermisosRepetidos(): void
    {
        $set = PermissionSet::fromCodes(['usuarios.ver', 'usuarios.ver']);

        self::assertSame(1, $set->count());
    }

    // ------------------------------------------------- bypass de superadmin

    public function testRolAdminPuedeTodoAunqueNoTengaPermisosAsignados(): void
    {
        $admin = $this->roles->findById(self::ID_ADMIN);
        self::assertNotNull($admin);
        self::assertTrue($admin->permissions()->isEmpty(), 'Parte sin permisos explícitos.');

        foreach (Permission::cases() as $permission) {
            self::assertTrue($admin->can($permission), $permission->value);
        }
    }

    public function testUnRolNormalSoloPuedeLoQueTieneConcedido(): void
    {
        $this->sync->execute((string) self::ID_USER, ['usuarios.ver']);

        $rol = $this->roles->findById(self::ID_USER);
        self::assertNotNull($rol);

        self::assertTrue($rol->can(Permission::UsuariosVer));
        self::assertFalse($rol->can(Permission::UsuariosCrear));
    }

    public function testNoSePuedenEditarLosPermisosDeRolAdmin(): void
    {
        $this->expectException(ProtectedRoleException::class);
        $this->sync->execute((string) self::ID_ADMIN, ['usuarios.ver']);
    }

    // --------------------------------------------------------- sincronización

    public function testGuardaYReemplazaElConjuntoCompleto(): void
    {
        $this->sync->execute((string) self::ID_USER, ['usuarios.ver', 'usuarios.crear']);
        $aplicados = $this->sync->execute((string) self::ID_USER, ['roles.ver']);

        self::assertSame(['roles.ver'], $aplicados);

        $rol = $this->roles->findById(self::ID_USER);
        self::assertNotNull($rol);
        self::assertFalse($rol->can(Permission::UsuariosVer), 'El permiso anterior debió irse.');
    }

    public function testUnaListaVaciaRevocaTodo(): void
    {
        $this->sync->execute((string) self::ID_USER, ['usuarios.ver']);
        $aplicados = $this->sync->execute((string) self::ID_USER, []);

        self::assertSame([], $aplicados);
    }

    /** Nadie puede concederse un permiso inventando un checkbox. */
    public function testIgnoraCodigosInventados(): void
    {
        $aplicados = $this->sync->execute(
            (string) self::ID_USER,
            ['usuarios.ver', 'sistema.borrar_todo']
        );

        self::assertSame(['usuarios.ver'], $aplicados);
    }

    public function testRechazaUnRolInexistente(): void
    {
        $this->expectException(RoleNotFoundException::class);
        $this->sync->execute('999', []);
    }

    // ------------------------------------------------------ efecto inmediato

    /**
     * Los permisos se leen de la base en cada petición, no de la sesión. Si se
     * cachearan al iniciar sesión, revocar un permiso a alguien conectado no
     * surtiría efecto hasta que cerrara sesión: un agujero real.
     */
    public function testRevocarSurteEfectoSinVolverAIniciarSesion(): void
    {
        $this->sync->execute((string) self::ID_USER, ['usuarios.ver']);
        $actor = $this->crearUsuario('ana', self::ID_USER);

        self::assertTrue($this->userCan()->execute($actor, Permission::UsuariosVer));

        // El "actor" sigue siendo el mismo objeto de sesión, sin re-login.
        $this->sync->execute((string) self::ID_USER, []);

        self::assertFalse(
            $this->userCan()->execute($actor, Permission::UsuariosVer),
            'Revocar debe notarse de inmediato.'
        );
    }

    public function testSinUsuarioAutenticadoNoHayPermisos(): void
    {
        self::assertFalse($this->userCan()->execute(null, Permission::PanelVer));
        self::assertTrue($this->userCan()->all(null)->isEmpty());
    }

    public function testParaRolAdminDevuelveElCatalogoCompleto(): void
    {
        $actor = $this->crearUsuario('jefa', self::ID_ADMIN);

        self::assertSame(
            count(Permission::cases()),
            $this->userCan()->all($actor)->count()
        );
    }

    // ---------------------------------------------------------------- matriz

    public function testLaMatrizMarcaRolAdminComoNoEditableYConTodo(): void
    {
        $matriz = (new GetPermissionMatrix($this->roles))->execute();

        $porNombre = [];
        foreach ($matriz['roles'] as $rol) {
            $porNombre[$rol['name']] = $rol;
        }

        self::assertFalse($porNombre[RoleName::ADMIN]['editable']);
        self::assertSame(count(Permission::cases()), $porNombre[RoleName::ADMIN]['total']);

        self::assertTrue($porNombre[RoleName::USER]['editable']);
    }

    public function testLaMatrizAgrupaTodosLosPermisosDelCatalogo(): void
    {
        $matriz = (new GetPermissionMatrix($this->roles))->execute();

        $total = 0;
        foreach ($matriz['groups'] as $permisos) {
            $total += count($permisos);
        }

        self::assertSame(count(Permission::cases()), $total);
    }
}
