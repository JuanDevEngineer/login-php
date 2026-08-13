<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\ValueObject\Email;

/** PUERTO de envío de correo. El adaptador real usa PHPMailer/SMTP. */
interface Mailer
{
    /**
     * @throws \RuntimeException si el envío falla de forma irrecuperable
     */
    public function send(Email $to, string $subject, string $htmlBody): void;
}
