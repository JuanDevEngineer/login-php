<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Port\Mailer;
use App\Domain\ValueObject\Email;
use App\Infrastructure\Config\Config;
use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/** ADAPTADOR SMTP. Las credenciales vienen del .env, nunca del código. */
final class PhpMailerMailer implements Mailer
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function send(Email $to, string $subject, string $htmlBody): void
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;
            $mail->SMTPAuth   = true;
            $mail->Host       = (string) $this->config->get('mail.host');
            $mail->Username   = (string) $this->config->get('mail.user');
            $mail->Password   = (string) $this->config->get('mail.pass');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $this->config->get('mail.port');

            $mail->setFrom(
                (string) $this->config->get('mail.from'),
                (string) $this->config->get('mail.from_name')
            );
            $mail->addAddress($to->value());

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
        } catch (PhpMailerException $e) {
            // El detalle va al log; hacia afuera solo un mensaje genérico, para
            // no filtrar host ni usuario SMTP en una respuesta HTTP.
            error_log('[mail] fallo de envío: ' . $mail->ErrorInfo);
            throw new \RuntimeException('No se pudo enviar el correo.', 0, $e);
        }
    }
}
