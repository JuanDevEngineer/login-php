<?php

declare(strict_types=1);

namespace Tests\Double;

use App\Domain\Port\SessionStorage;

/** Sesión en memoria: los tests no arrancan una sesión PHP real. */
final class NullSession implements SessionStorage
{
    /** @var array<string, mixed> */
    private array $data = [];

    public bool $regenerated = false;
    public bool $destroyed = false;

    public function start(): void
    {
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /** @param mixed $value */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function destroy(): void
    {
        $this->data = [];
        $this->destroyed = true;
    }

    public function regenerate(): void
    {
        $this->regenerated = true;
    }
}
