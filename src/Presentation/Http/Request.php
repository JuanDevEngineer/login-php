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

    /**
     * ¿PHP descartó el cuerpo por superar post_max_size?
     *
     * Cuando eso pasa, $_POST y $_FILES quedan vacíos pero CONTENT_LENGTH sigue
     * informando el tamaño real. Es la única forma de distinguir "subieron algo
     * enorme" de "mandaron un formulario vacío", y sin distinguirlo el error
     * aparece como un fallo de CSRF.
     */
    public function wasTruncatedByPostMaxSize(): bool
    {
        if ($this->body !== [] || $this->files !== []) {
            return false;
        }

        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength <= 0) {
            return false;
        }

        $limit = self::toBytes((string) ini_get('post_max_size'));

        return $limit > 0 && $contentLength > $limit;
    }

    /** Convierte "8M", "512K", "1G" a bytes. */
    private static function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit   = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;

        switch ($unit) {
            case 'g':
                return $number * 1024 * 1024 * 1024;
            case 'm':
                return $number * 1024 * 1024;
            case 'k':
                return $number * 1024;
            default:
                return $number;
        }
    }

    public function isAjax(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}
