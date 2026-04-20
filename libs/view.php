<?php

/**
 * Renderizador de vistas. Incluye `views/<name>.php` con las variables que se
 * pasen como segundo argumento disponibles en el scope local.
 */
class View
{
    public function render(string $name, array $data = []): void
    {
        if (!empty($data)) {
            extract($data, EXTR_SKIP);
        }
        $file = __DIR__ . '/../views/' . $name . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'Vista no encontrada: ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            return;
        }
        require $file;
    }
}
