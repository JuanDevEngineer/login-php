<?php

declare(strict_types=1);

namespace App\Presentation\Http;

use App\Application\Dto\AuthenticatedUser;
use App\Application\UseCase\Auth\GetAuthenticatedUser;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\DomainException;
use App\Infrastructure\Container;
use App\Infrastructure\Security\CsrfGuard;

/**
 * Router con tabla de rutas explícita.
 *
 * El router anterior hacía `new $_GET['controller']` y llamaba a
 * `$_GET['action']`: cualquier clase cargada del proyecto era instanciable y
 * cualquier método público, invocable desde la URL. Acá solo existe lo que está
 * declarado en la tabla, y cada ruta lleva escrito su nivel de acceso.
 */
final class Router
{
    /** @var Route[] */
    private array $routes = [];

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $r = function (string $method, string $path, string $controller, string $action, string $access) {
            $this->routes[] = new Route($method, $path, $controller, $action, $access);
        };

        // --- Público -------------------------------------------------------
        $r('GET',  '/',                  'AuthController', 'showLogin',       Route::PUBLIC_ACCESS);
        $r('GET',  '/login',             'AuthController', 'showLogin',       Route::PUBLIC_ACCESS);
        $r('POST', '/login',             'AuthController', 'login',           Route::PUBLIC_ACCESS);
        $r('GET',  '/register',          'AuthController', 'showRegister',    Route::PUBLIC_ACCESS);
        $r('POST', '/register',          'AuthController', 'register',        Route::PUBLIC_ACCESS);
        $r('GET',  '/logout',            'AuthController', 'logout',          Route::PUBLIC_ACCESS);
        $r('POST', '/logout',            'AuthController', 'logout',          Route::PUBLIC_ACCESS);

        // --- Recuperación de contraseña ------------------------------------
        $r('GET',  '/password/forgot',   'PasswordController', 'showForgot',  Route::PUBLIC_ACCESS);
        $r('POST', '/password/forgot',   'PasswordController', 'sendLink',    Route::PUBLIC_ACCESS);
        $r('GET',  '/password/reset',    'PasswordController', 'showReset',   Route::PUBLIC_ACCESS);
        $r('POST', '/password/reset',    'PasswordController', 'reset',       Route::PUBLIC_ACCESS);

        // --- Dashboard (requiere sesión) -----------------------------------
        $r('GET',  '/dashboard',         'DashboardController', 'index',      Route::AUTH);
        $r('GET',  '/profile',           'DashboardController', 'profile',    Route::AUTH);
        $r('GET',  '/users',             'DashboardController', 'manageUsers', Route::ADMIN);

        // --- API JSON del gestor -------------------------------------------
        $r('POST', '/api/users/create',  'UserApiController', 'create',        Route::ADMIN);
        $r('POST', '/api/users/list',    'UserApiController', 'list',          Route::ADMIN);
        $r('POST', '/api/users/find',    'UserApiController', 'find',          Route::ADMIN);
        $r('POST', '/api/users/update',  'UserApiController', 'update',        Route::ADMIN);
        $r('POST', '/api/users/toggle',  'UserApiController', 'toggleStatus',  Route::ADMIN);
        $r('GET',  '/api/users/names',   'UserApiController', 'names',         Route::ADMIN);

        // --- Roles (solo admin) --------------------------------------------
        $r('GET',  '/roles',             'DashboardController', 'manageRoles', Route::ADMIN);
        $r('GET',  '/api/roles',         'UserApiController', 'roles',         Route::ADMIN);
        $r('GET',  '/api/roles/list',    'RoleApiController', 'list',          Route::ADMIN);
        $r('POST', '/api/roles/create',  'RoleApiController', 'create',        Route::ADMIN);
        $r('POST', '/api/roles/update',  'RoleApiController', 'update',        Route::ADMIN);
        $r('POST', '/api/roles/delete',  'RoleApiController', 'delete',        Route::ADMIN);
        $r('POST', '/api/profile/image', 'UserApiController', 'uploadImage',   Route::AUTH);
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->match($request);

        if ($route === null) {
            return $this->notFound($request);
        }

        $user = $this->container->get(GetAuthenticatedUser::class)->execute();

        $denied = $this->enforceAccess($route, $user, $request);
        if ($denied !== null) {
            return $denied;
        }

        // CSRF en todo lo que muta estado.
        if ($request->isPost() && !$this->csrfValid($request)) {
            return $this->csrfFailure($request);
        }

        return $this->invoke($route, $request, $user);
    }

    private function match(Request $request): ?Route
    {
        $path = $this->normalize($request->path());

        foreach ($this->routes as $route) {
            if ($route->path === $path && $route->method === $request->method()) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Quita el subdirectorio en el que esté montado el proyecto, para que las
     * rutas de la tabla funcionen igual en /login-php que en la raíz del host.
     */
    private function normalize(string $path): string
    {
        $basePath = parse_url($this->container->config()->baseUrl(), PHP_URL_PATH);

        if (is_string($basePath) && $basePath !== '' && $basePath !== '/') {
            $basePath = rtrim($basePath, '/');
            if (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
            }
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function enforceAccess(Route $route, ?AuthenticatedUser $user, Request $request): ?Response
    {
        if ($route->access === Route::PUBLIC_ACCESS) {
            return null;
        }

        if ($user === null) {
            return $this->expectsJson($request)
                ? Response::json(['success' => false, 'error' => 'No autenticado.'], 401)
                : Response::redirect($this->container->config()->baseUrl() . '/login');
        }

        if ($route->access === Route::ADMIN && !$user->isAdmin()) {
            return $this->expectsJson($request)
                ? Response::json(['success' => false, 'error' => 'No autorizado.'], 403)
                : $this->render('errors/403', ['message' => 'No tenés permiso para ver esta página.'], 403);
        }

        return null;
    }

    private function csrfValid(Request $request): bool
    {
        return $this->container->get(CsrfGuard::class)
            ->isValid($request->raw(CsrfGuard::FIELD_NAME, ''));
    }

    private function csrfFailure(Request $request): Response
    {
        return $this->expectsJson($request)
            ? Response::json(['success' => false, 'error' => 'Token de seguridad inválido. Recargá la página.'], 419)
            : $this->render('errors/419', [], 419);
    }

    private function invoke(Route $route, Request $request, ?AuthenticatedUser $user): Response
    {
        $class = 'App\\Presentation\\Controller\\' . $route->controller;

        if (!class_exists($class)) {
            throw new \LogicException('Controlador inexistente: ' . $class);
        }

        $controller = new $class($this->container, $user);

        if (!method_exists($controller, $route->action)) {
            throw new \LogicException(sprintf('Acción inexistente: %s::%s', $class, $route->action));
        }

        try {
            return $controller->{$route->action}($request);
        } catch (AccessDeniedException $e) {
            return $this->expectsJson($request)
                ? Response::json(['success' => false, 'error' => $e->getMessage()], 403)
                : $this->render('errors/403', ['message' => $e->getMessage()], 403);
        } catch (DomainException $e) {
            // Errores de negocio: son culpa del input, no del sistema. 422.
            return $this->expectsJson($request)
                ? Response::json(['success' => false, 'error' => $e->getMessage()], 422)
                : $this->render('errors/generic', ['message' => $e->getMessage()], 422);
        }
    }

    private function notFound(Request $request): Response
    {
        return $this->expectsJson($request)
            ? Response::json(['success' => false, 'error' => 'Recurso no encontrado.'], 404)
            : $this->render('errors/404', [], 404);
    }

    private function expectsJson(Request $request): bool
    {
        return $request->isAjax() || strpos($this->normalize($request->path()), '/api/') === 0;
    }

    private function render(string $view, array $data, int $status): Response
    {
        return Response::html(
            $this->container->view()->render($view, $data),
            $status
        );
    }
}
