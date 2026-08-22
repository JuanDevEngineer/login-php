<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\UseCase\Auth\GetAuthenticatedUser;
use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\Auth\LogoutUser;
use App\Application\UseCase\Auth\RegisterUser;
use App\Application\UseCase\Dashboard\GetDashboardMetrics;
use App\Application\UseCase\Password\RequestPasswordReset;
use App\Application\UseCase\Password\ResetPassword;
use App\Application\UseCase\Password\ValidateRecoveryToken;
use App\Application\UseCase\Permission\GetPermissionMatrix;
use App\Application\UseCase\Permission\SyncRolePermissions;
use App\Application\UseCase\Permission\UserCan;
use App\Application\UseCase\Role\CreateRole;
use App\Application\UseCase\Role\DeleteRole;
use App\Application\UseCase\Role\ListRolesDetailed;
use App\Application\UseCase\Role\UpdateRole;
use App\Application\UseCase\User\ChangeProfileImage;
use App\Application\UseCase\User\CreateUser;
use App\Application\UseCase\User\FindUser;
use App\Application\UseCase\User\ListRoles;
use App\Application\UseCase\User\ListUserNames;
use App\Application\UseCase\User\ListUsers;
use App\Application\UseCase\User\RemoveProfileImage;
use App\Application\UseCase\User\ToggleUserStatus;
use App\Application\UseCase\User\UpdateUser;
use App\Domain\Port\Clock;
use App\Domain\Port\ImageStorage;
use App\Domain\Port\LoginLog;
use App\Domain\Port\Mailer;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\RoleRepository;
use App\Domain\Port\SessionStorage;
use App\Domain\Port\TokenGenerator;
use App\Domain\Port\UserRepository;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Mail\NullMailer;
use App\Infrastructure\Mail\PhpMailerMailer;
use App\Infrastructure\Persistence\Pdo\PdoConnection;
use App\Infrastructure\Persistence\Pdo\PdoLoginLog;
use App\Infrastructure\Persistence\Pdo\PdoRoleRepository;
use App\Infrastructure\Persistence\Pdo\PdoUserRepository;
use App\Infrastructure\Security\BcryptPasswordHasher;
use App\Infrastructure\Security\CsrfGuard;
use App\Infrastructure\Security\RandomTokenGenerator;
use App\Infrastructure\Session\NativeSession;
use App\Infrastructure\Storage\LocalImageStorage;
use App\Presentation\View\ViewRenderer;

/**
 * Composition root.
 *
 * Es el ÚNICO lugar del proyecto donde un puerto se ata a su adaptador
 * concreto. Ni el dominio ni los casos de uso mencionan PDO, PHPMailer o
 * $_SESSION: reciben interfaces y este contenedor decide qué implementación
 * usar. Cambiar de motor de correo o de base de datos se resuelve editando
 * solamente este archivo.
 */
final class Container
{
    private Config $config;
    /** @var array<string, object> */
    private array $instances = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function config(): Config
    {
        return $this->config;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id)
    {
        if (!isset($this->instances[$id])) {
            $this->instances[$id] = $this->make($id);
        }

        /** @var T */
        return $this->instances[$id];
    }

    public function view(): ViewRenderer
    {
        return $this->get(ViewRenderer::class);
    }

    private function make(string $id): object
    {
        switch ($id) {
            // ---------------------------------------------- infraestructura
            case PdoConnection::class:
                return new PdoConnection($this->config);

            case UserRepository::class:
                return new PdoUserRepository($this->get(PdoConnection::class));

            case RoleRepository::class:
                return new PdoRoleRepository($this->get(PdoConnection::class));

            case LoginLog::class:
                return new PdoLoginLog($this->get(PdoConnection::class));

            case PasswordHasher::class:
                return new BcryptPasswordHasher();

            case TokenGenerator::class:
                return new RandomTokenGenerator();

            case SessionStorage::class:
                return new NativeSession();

            case Clock::class:
                return new SystemClock();

            case Mailer::class:
                // Sin SMTP configurado caemos al mailer de log, para que en
                // local el flujo de recuperación funcione sin credenciales.
                return $this->config->get('mail.host') === ''
                    ? new NullMailer()
                    : new PhpMailerMailer($this->config);

            case ImageStorage::class:
                return new LocalImageStorage(
                    (string) $this->config->get('uploads.path'),
                    (int) $this->config->get('uploads.max')
                );

            case CsrfGuard::class:
                return new CsrfGuard(
                    $this->get(SessionStorage::class),
                    $this->get(TokenGenerator::class)
                );

            case ViewRenderer::class:
                $renderer = new ViewRenderer($this->config->get('app.root') . '/resources/views');
                $renderer->share('baseUrl', $this->config->baseUrl());
                $renderer->share('csrf', $this->get(CsrfGuard::class));
                $renderer->share('uploadMaxBytes', (int) $this->config->get('uploads.max'));
                return $renderer;

            // ------------------------------------------------- casos de uso
            case LoginUser::class:
                return new LoginUser(
                    $this->get(UserRepository::class),
                    $this->get(PasswordHasher::class),
                    $this->get(SessionStorage::class),
                    $this->get(LoginLog::class),
                    $this->get(Clock::class)
                );

            case GetDashboardMetrics::class:
                return new GetDashboardMetrics(
                    $this->get(UserRepository::class),
                    $this->get(LoginLog::class),
                    $this->get(Clock::class)
                );

            case RegisterUser::class:
                return new RegisterUser(
                    $this->get(UserRepository::class),
                    $this->get(RoleRepository::class),
                    $this->get(PasswordHasher::class),
                    $this->get(Clock::class)
                );

            case LogoutUser::class:
                return new LogoutUser($this->get(SessionStorage::class));

            case GetAuthenticatedUser::class:
                return new GetAuthenticatedUser($this->get(SessionStorage::class));

            case RequestPasswordReset::class:
                return new RequestPasswordReset(
                    $this->get(UserRepository::class),
                    $this->get(TokenGenerator::class),
                    $this->get(Mailer::class),
                    $this->get(Clock::class),
                    $this->config->baseUrl()
                );

            case ValidateRecoveryToken::class:
                return new ValidateRecoveryToken(
                    $this->get(UserRepository::class),
                    $this->get(Clock::class)
                );

            case ResetPassword::class:
                return new ResetPassword(
                    $this->get(ValidateRecoveryToken::class),
                    $this->get(UserRepository::class),
                    $this->get(PasswordHasher::class)
                );

            case ListUsers::class:
                return new ListUsers($this->get(UserRepository::class));

            case FindUser::class:
                return new FindUser($this->get(UserRepository::class));

            case ListUserNames::class:
                return new ListUserNames($this->get(UserRepository::class));

            case ListRoles::class:
                return new ListRoles($this->get(RoleRepository::class));

            case UserCan::class:
                return new UserCan(
                    $this->get(UserRepository::class),
                    $this->get(RoleRepository::class)
                );

            case GetPermissionMatrix::class:
                return new GetPermissionMatrix($this->get(RoleRepository::class));

            case SyncRolePermissions::class:
                return new SyncRolePermissions($this->get(RoleRepository::class));

            case ListRolesDetailed::class:
                return new ListRolesDetailed(
                    $this->get(RoleRepository::class),
                    $this->get(UserRepository::class)
                );

            case CreateRole::class:
                return new CreateRole($this->get(RoleRepository::class));

            case UpdateRole::class:
                return new UpdateRole(
                    $this->get(RoleRepository::class),
                    $this->get(UserRepository::class)
                );

            case DeleteRole::class:
                return new DeleteRole(
                    $this->get(RoleRepository::class),
                    $this->get(UserRepository::class)
                );

            case UpdateUser::class:
                return new UpdateUser(
                    $this->get(UserRepository::class),
                    $this->get(RoleRepository::class)
                );

            case ToggleUserStatus::class:
                return new ToggleUserStatus($this->get(UserRepository::class));

            case CreateUser::class:
                return new CreateUser(
                    $this->get(UserRepository::class),
                    $this->get(RoleRepository::class),
                    $this->get(PasswordHasher::class),
                    $this->get(ImageStorage::class),
                    $this->get(Clock::class)
                );

            case RemoveProfileImage::class:
                return new RemoveProfileImage(
                    $this->get(UserRepository::class),
                    $this->get(ImageStorage::class),
                    $this->get(UserCan::class)
                );

            case ChangeProfileImage::class:
                return new ChangeProfileImage(
                    $this->get(UserRepository::class),
                    $this->get(ImageStorage::class),
                    $this->get(UserCan::class)
                );
        }

        throw new \InvalidArgumentException('El contenedor no sabe construir: ' . $id);
    }
}
