<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Port\Mailer;
use App\Domain\ValueObject\Email;

/**
 * Adaptador de desarrollo: en lugar de enviar, escribe el correo al log.
 * Se activa solo cuando no hay SMTP_HOST configurado, así el flujo de
 * recuperación es probable en local sin credenciales reales.
 */
final class NullMailer implements Mailer
{
    public function send(Email $to, string $subject, string $htmlBody): void
    {
        error_log(sprintf(
            "[mail:null] Para: %s | Asunto: %s\n%s",
            $to->value(),
            $subject,
            strip_tags($htmlBody)
        ));
    }
}
