<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

/**
 * Configuración tipada de la aplicación. Es el único punto que lee variables de
 * entorno; el resto del código recibe valores ya resueltos.
 */
final class Config
{
    /** @var array<string, mixed> */
    private array $values;

    private function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function fromEnvironment(string $projectRoot): self
    {
        Env::load($projectRoot . '/.env');

        return new self([
            'app.env'       => (string) Env::get('APP_ENV', 'production'),
            'app.debug'     => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
            'app.base_url'  => rtrim((string) Env::get('BASE_URL', ''), '/'),
            'app.timezone'  => (string) Env::get('APP_TIMEZONE', 'America/Bogota'),
            'app.root'      => $projectRoot,

            'db.host'       => (string) Env::get('DB_HOST', '127.0.0.1'),
            'db.port'       => (int) Env::get('DB_PORT', '3306'),
            'db.name'       => (string) Env::get('DB_NAME', 'test'),
            'db.user'       => (string) Env::get('DB_USER', 'root'),
            'db.pass'       => (string) Env::get('DB_PASS', ''),
            'db.charset'    => (string) Env::get('DB_CHARSET', 'utf8mb4'),

            'mail.host'     => (string) Env::get('SMTP_HOST', ''),
            'mail.user'     => (string) Env::get('SMTP_USER', ''),
            'mail.pass'     => (string) Env::get('SMTP_PASS', ''),
            'mail.port'     => (int) Env::get('SMTP_PORT', '587'),
            'mail.from'     => (string) Env::get('MAIL_FROM', 'no-reply@localhost'),
            'mail.from_name'=> (string) Env::get('MAIL_FROM_NAME', 'Admin'),

            'uploads.path'  => $projectRoot . '/assets/uploads',
            'uploads.max'   => (int) Env::get('UPLOAD_MAX_BYTES', '2097152'), // 2 MB
        ]);
    }

    /** @return mixed */
    public function get(string $key, $default = null)
    {
        return $this->values[$key] ?? $default;
    }

    public function isDevelopment(): bool
    {
        return $this->values['app.env'] === 'development';
    }

    public function baseUrl(): string
    {
        return (string) $this->values['app.base_url'];
    }
}
