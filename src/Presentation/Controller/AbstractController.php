<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Dto\AuthenticatedUser;
use App\Infrastructure\Container;
use App\Presentation\Http\Response;

/**
 * Base de los controladores. Deliberadamente delgada: los controladores
 * traducen HTTP a llamadas de casos de uso y nada más. Ninguna regla de negocio
 * vive acá.
 */
abstract class AbstractController
{
    protected Container $container;
    protected ?AuthenticatedUser $user;

    public function __construct(Container $container, ?AuthenticatedUser $user)
    {
        $this->container = $container;
        $this->user      = $user;
    }

    /**
     * @template T of object
     * @param class-string<T> $useCase
     * @return T
     */
    protected function useCase(string $useCase)
    {
        return $this->container->get($useCase);
    }

    protected function baseUrl(): string
    {
        return $this->container->config()->baseUrl();
    }

    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        $data['authUser'] = $this->user;

        return Response::html(
            $this->container->view()->render($template, $data),
            $status
        );
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect($this->baseUrl() . $path);
    }

    /** @param mixed $data */
    protected function json($data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function ok(array $extra = []): Response
    {
        return $this->json(array_merge(['success' => true], $extra));
    }

    protected function fail(string $message, int $status = 422): Response
    {
        return $this->json(['success' => false, 'error' => $message], $status);
    }
}
