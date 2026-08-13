<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;

/**
 * Token de recuperación de contraseña.
 *
 * Se persiste como "<selector>:<hash del verificador>:<timestamp de expiración>".
 * El selector viaja en claro y sirve para buscar la fila sin hacer una búsqueda
 * por LIKE; el verificador solo existe en el enlace que recibe el usuario y en
 * base de datos guardamos únicamente su hash. Así, si alguien lee la tabla no
 * puede reconstruir un enlace válido.
 */
final class RecoveryToken
{
    public const TTL_SECONDS = 1800; // 30 minutos

    private string $selector;
    private string $verifierHash;
    private int $expiresAt;

    private function __construct(string $selector, string $verifierHash, int $expiresAt)
    {
        if ($selector === '' || $verifierHash === '') {
            throw new InvalidArgumentException('Token de recuperación mal formado.');
        }
        $this->selector     = $selector;
        $this->verifierHash = $verifierHash;
        $this->expiresAt    = $expiresAt;
    }

    /**
     * Construye el token a partir de material aleatorio recién generado.
     * El verificador en claro NO se guarda: lo devuelve quien llama para
     * armar el enlace del correo.
     */
    public static function issue(string $selector, string $verifier, int $issuedAt): self
    {
        return new self(
            $selector,
            hash('sha256', $verifier),
            $issuedAt + self::TTL_SECONDS
        );
    }

    /** Rehidrata desde la columna `recover` de la base de datos. */
    public static function fromStorage(string $stored): self
    {
        $parts = explode(':', $stored);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Token de recuperación mal formado.');
        }

        return new self($parts[0], $parts[1], (int) $parts[2]);
    }

    public function toStorage(): string
    {
        return $this->selector . ':' . $this->verifierHash . ':' . $this->expiresAt;
    }

    public function selector(): string
    {
        return $this->selector;
    }

    public function isExpired(int $now): bool
    {
        return $now >= $this->expiresAt;
    }

    /** Comparación en tiempo constante contra el verificador que llegó por URL. */
    public function matches(string $verifier): bool
    {
        return hash_equals($this->verifierHash, hash('sha256', $verifier));
    }

    public function expiresAt(): int
    {
        return $this->expiresAt;
    }
}
