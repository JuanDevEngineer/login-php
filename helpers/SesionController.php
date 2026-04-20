<?php

if (session_status() === PHP_SESSION_NONE) {
    // Endurecer la cookie de sesión cuando sea posible.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

class SesionController
{
    function __construct() {}

    protected function isUser()
    {
        return isset($_SESSION['usuario']);
    }

    protected function isAdmin()
    {
        return $this->isUser() && ($_SESSION['rol'] ?? '') === 'ROL_ADMIN';
    }

    /**
     * Exige sesión activa. Redirige al login si no la hay.
     */
    protected function requireAuth()
    {
        if (!$this->isUser()) {
            $this->redirect('/App/acceso');
            exit;
        }
    }

    /**
     * Exige rol ADMIN. 403 en caso contrario.
     */
    protected function requireAdmin()
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo 'No autorizado';
            exit;
        }
    }

    /**
     * Versión JSON de requireAuth (para endpoints AJAX).
     */
    protected function requireAuthJson()
    {
        if (!$this->isUser()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'msg' => 'no autenticado']);
            exit;
        }
    }

    /**
     * Versión JSON de requireAdmin (para endpoints AJAX).
     */
    protected function requireAdminJson()
    {
        $this->requireAuthJson();
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => 'no autorizado']);
            exit;
        }
    }

    /**
     * Verifica CSRF y corta con 419 si falla (respuesta JSON).
     */
    protected function verifyCsrfJson()
    {
        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['success' => false, 'msg' => 'token CSRF inválido']);
            exit;
        }
    }

    protected function redirect($location)
    {
        header('Location:' . BASE_URL . $location);
        return;
    }

    protected function validateFormatImage($file)
    {
        $type = ['image/gif', 'image/jpeg', 'image/jpg', 'image/png'];
        return in_array($file, $type, true);
    }

    protected function validateSize($fileSize)
    {
        return $fileSize > 0 && $fileSize < 10_000_000;
    }
}
