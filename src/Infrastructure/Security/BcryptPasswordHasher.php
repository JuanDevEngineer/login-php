<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Port\PasswordHasher;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\PlainPassword;

final class BcryptPasswordHasher implements PasswordHasher
{
    private const COST = 12;

    public function hash(PlainPassword $plain): HashedPassword
    {
        $hash = password_hash($plain->value(), PASSWORD_BCRYPT, ['cost' => self::COST]);

        if (!is_string($hash) || $hash === '') {
            throw new \RuntimeException('No se pudo generar el hash de la contraseña.');
        }

        return HashedPassword::fromHash($hash);
    }

    public function verify(PlainPassword $plain, HashedPassword $hash): bool
    {
        return password_verify($plain->value(), $hash->value());
    }

    public function needsRehash(HashedPassword $hash): bool
    {
        return password_needs_rehash($hash->value(), PASSWORD_BCRYPT, ['cost' => self::COST]);
    }
}
