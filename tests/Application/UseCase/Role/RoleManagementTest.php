<?php

declare(strict_types=1);

namespace Tests\Application\UseCase\Role;

use App\Application\UseCase\Role\CreateRole;
use App\Application\UseCase\Role\DeleteRole;
use App\Application\UseCase\Role\ListRolesDetailed;
use App\Application\UseCase\Role\UpdateRole;
use App\Application\UseCase\User\CreateUser;
use App\Application\Dto\NewUserData;
use App\Domain\Exception\InvalidArgumentException;
use App\Domain\Exception\ProtectedRoleException;
use App\Domain\Exception\RoleAlreadyExistsException;
use App\Domain\Exception\RoleInUseException;
use App\Domain\Exception\RoleNotFoundException;
use App\Domain\ValueObject\RoleName;
use App\Infrastructure\Security\BcryptPasswordHasher;
use PHPUnit\Framework\TestCase;
use Tests\Double\FixedClock;
use Tests\Double\InMemoryRoleRepository;
use Tests\Double\InMemoryUserRepository;
use Tests\Double\SpyImageStorage;

final class RoleManagementTest extends TestCase
{
    private InMemoryRoleRepository $roles;
    private InMemoryUserRepository $users;

    private CreateRole $create;
    private UpdateRole $update;
    private DeleteRole $delete;

    /** Ids de los roles de sistema, tal como los deja la migración. */
    private const ID_ADMIN = 1;
    private const ID_USER  = 2;

    protected function setUp(): void
    {
        $this->roles = new InMemoryRoleRepository();
        $this->users = new InMemoryUserRepository();

        $this->create = new CreateRole($this->roles);
        $this->update = new UpdateRole($this->roles, $this->users);
        $this->delete = new DeleteRole($this->roles, $this->users);
    }

    /** Crea un usuario real con el rol indicado, para poblar el conteo. */
    private function asignarUsuarioAlRol(int $roleId, string $username): void
    {
        $useCase = new CreateUser(
            $this->users,
            $this->roles,
            new BcryptPasswordHasher(),
            new SpyImageStorage(),
            new FixedClock()
        );

        $useCase->execute(new NewUserData(
            $username,
            $username . '@example.com',
            'contrasena-larga',
            'contrasena-larga',
            (string) $roleId
        ));
    }

    // ------------------------------------------------------------------ crear

    public function testCreaUnRolNuevo(): void
    {
        $result = $this->create->execute('ROL_VENTAS');

        self::assertSame('ROL_VENTAS', $result['rol_usuario']);
        self::assertFalse($result['es_sistema']);
        self::assertSame(0, $result['usuarios']);
        self::assertTrue($result['editable']);
        self::assertTrue($result['eliminable']);
    }

    public function testNormalizaElNombreAMayusculas(): void
    {
        $result = $this->create->execute('  rol_soporte  ');

        self::assertSame('ROL_SOPORTE', $result['rol_usuario']);
    }

    public function testRechazaNombreDuplicadoAunConDistintaCaja(): void
    {
        $this->create->execute('ROL_VENTAS');

        $this->expectException(RoleAlreadyExistsException::class);
        $this->create->execute('rol_ventas');
    }

    public function testRechazaNombreConCaracteresInvalidos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->create->execute('rol ventas!');
    }

    public function testRechazaNombreDemasiadoCorto(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->create->execute('AB');
    }

    public function testUnRolCreadoNuncaEsDeSistema(): void
    {
        $created = $this->create->execute('ROL_VENTAS');
        $role = $this->roles->findById($created['id_rol']);

        self::assertNotNull($role);
        self::assertFalse($role->isSystem());
    }

    // ----------------------------------------------------------------- editar

    public function testRenombraUnRolPersonalizado(): void
    {
        $created = $this->create->execute('ROL_VENTAS');

        $result = $this->update->execute((string) $created['id_rol'], 'ROL_COMERCIAL');

        self::assertSame('ROL_COMERCIAL', $result['rol_usuario']);
    }

    public function testNoPermiteRenombrarRolAdmin(): void
    {
        $this->expectException(ProtectedRoleException::class);
        $this->update->execute((string) self::ID_ADMIN, 'ROL_JEFE');
    }

    public function testNoPermiteRenombrarRolUser(): void
    {
        $this->expectException(ProtectedRoleException::class);
        $this->update->execute((string) self::ID_USER, 'ROL_BASICO');
    }

    public function testRolAdminSigueIntactoTrasUnIntentoDeRenombrado(): void
    {
        try {
            $this->update->execute((string) self::ID_ADMIN, 'ROL_JEFE');
        } catch (ProtectedRoleException $e) {
            // esperado
        }

        $admin = $this->roles->findById(self::ID_ADMIN);
        self::assertNotNull($admin);
        self::assertSame(RoleName::ADMIN, $admin->name()->value());
    }

    public function testRechazaRenombrarAUnNombreYaUsado(): void
    {
        $a = $this->create->execute('ROL_VENTAS');
        $this->create->execute('ROL_SOPORTE');

        $this->expectException(RoleAlreadyExistsException::class);
        $this->update->execute((string) $a['id_rol'], 'ROL_SOPORTE');
    }

    public function testPermiteGuardarUnRolConSuMismoNombre(): void
    {
        $created = $this->create->execute('ROL_VENTAS');

        $result = $this->update->execute((string) $created['id_rol'], 'ROL_VENTAS');

        self::assertSame('ROL_VENTAS', $result['rol_usuario']);
    }

    public function testRechazaEditarUnRolInexistente(): void
    {
        $this->expectException(RoleNotFoundException::class);
        $this->update->execute('999', 'ROL_LOQUESEA');
    }

    // --------------------------------------------------------------- eliminar

    public function testEliminaUnRolSinUsuarios(): void
    {
        $created = $this->create->execute('ROL_VENTAS');
        $antes = $this->roles->count();

        $this->delete->execute((string) $created['id_rol']);

        self::assertSame($antes - 1, $this->roles->count());
        self::assertNull($this->roles->findById($created['id_rol']));
    }

    public function testNoPermiteEliminarUnRolDeSistema(): void
    {
        $this->expectException(ProtectedRoleException::class);
        $this->delete->execute((string) self::ID_ADMIN);
    }

    public function testNoPermiteEliminarUnRolConUsuariosAsignados(): void
    {
        $created = $this->create->execute('ROL_VENTAS');
        $this->asignarUsuarioAlRol($created['id_rol'], 'vendedor');

        $this->expectException(RoleInUseException::class);
        $this->delete->execute((string) $created['id_rol']);
    }

    public function testElMensajeDeRolEnUsoIndicaCuantosUsuariosHay(): void
    {
        $created = $this->create->execute('ROL_VENTAS');
        $this->asignarUsuarioAlRol($created['id_rol'], 'vendedor.uno');
        $this->asignarUsuarioAlRol($created['id_rol'], 'vendedor.dos');

        try {
            $this->delete->execute((string) $created['id_rol']);
            self::fail('Se esperaba RoleInUseException.');
        } catch (RoleInUseException $e) {
            self::assertStringContainsString('2', $e->getMessage());
            self::assertStringContainsString('ROL_VENTAS', $e->getMessage());
        }
    }

    public function testRechazaEliminarUnRolInexistente(): void
    {
        $this->expectException(RoleNotFoundException::class);
        $this->delete->execute('999');
    }

    // ----------------------------------------------------------------- listar

    public function testElListadoIncluyeElConteoDeUsuariosYLasBanderas(): void
    {
        $created = $this->create->execute('ROL_VENTAS');
        $this->asignarUsuarioAlRol($created['id_rol'], 'vendedor');

        $listado = (new ListRolesDetailed($this->roles, $this->users))->execute();

        $porNombre = [];
        foreach ($listado as $fila) {
            $porNombre[$fila['rol_usuario']] = $fila;
        }

        self::assertSame(1, $porNombre['ROL_VENTAS']['usuarios']);
        self::assertFalse($porNombre['ROL_VENTAS']['eliminable'], 'Tiene usuarios: no debe ser eliminable.');
        self::assertTrue($porNombre['ROL_VENTAS']['editable']);

        self::assertTrue($porNombre[RoleName::ADMIN]['es_sistema']);
        self::assertFalse($porNombre[RoleName::ADMIN]['editable']);
        self::assertFalse($porNombre[RoleName::ADMIN]['eliminable']);
    }
}
