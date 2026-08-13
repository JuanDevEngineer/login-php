<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\PlainPassword;

/** PUERTO de hashing. El dominio no sabe si por debajo es bcrypt o argon2. */
interface PasswordHasher
{
    public function hash(PlainPassword $plain): HashedPassword;

    public function verify(PlainPassword $plain, HashedPassword $hash): bool;

    /** ¿El hash usa parámetros viejos y conviene regenerarlo al vuelo? */
    public function needsRehash(HashedPassword $hash): bool;
}
