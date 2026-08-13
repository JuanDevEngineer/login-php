<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/** Respuesta HTTP. Nada se emite hasta que se llama a send(). */
class Response
{
    protected string $body;
    protected int $status;
    /** @var array<string, string> */
    protected array $headers;

    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body    = $body;
        $this->status  = $status;
        $this->headers = $headers;
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** @param mixed $data */
    public static function json($data, int $status = 200): self
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new self(
            $encoded === false ? '{}' : $encoded,
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }

        echo $this->body;
    }
}
