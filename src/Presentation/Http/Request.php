<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/** Envoltorio inmutable de la petición. Aísla las superglobales. */
final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $files;

    public function __construct(string $method, string $path, array $query, array $body, array $files)
    {
        $this->method = strtoupper($method);
        $this->path   = $path;
        $this->query  = $query;
        $this->body   = $body;
        $this->files  = $files;
    }

    public static function fromGlobals(): self
    {
        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);

        return new self(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            is_string($path) ? $path : '/',
            $_GET,
            $_POST,
            $_FILES
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    public function input(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $default;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /** Sin trim: para contraseñas, donde un espacio inicial o final es significativo. */
    public function raw(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isAjax(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}
