<?php
require_once 'model/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AppController extends SesionController
{
    public $view;
    public $mail;

    public function __construct()
    {
        $this->view = new View();
        $this->mail = new MailController();
    }

    public function acceso()
    {
        $this->view->render("app/login");
    }

    public function registro()
    {
        $this->view->render("app/register");
    }

    public function recover()
    {
        $this->view->render('app/recover');
    }

    public function signIn()
    {
        $response_login = [];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'método no permitido', 'status' => false]);
            return;
        }

        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['error' => 'token CSRF inválido', 'status' => false]);
            return;
        }

        try {
            $nombre   = trim($_POST['name'] ?? '');
            $password = trim($_POST['pass'] ?? '');

            if ($nombre === '' || $password === '') {
                echo json_encode(['errorinputs' => 'Todos los campos son obligatorios!', 'status' => false]);
                return;
            }

            $userLogin = new User();
            $userLogin->setUsername($nombre);
            $userLogin->setPassword($password);

            $response_login = $userLogin->login();

            if (!empty($response_login['status'])) {
                // Regenerar el ID de sesión para evitar session fixation.
                session_regenerate_id(true);

                $_SESSION['id_k']    = $response_login['data']['id_usuario'];
                $_SESSION['email']   = $response_login['data']['email'];
                $_SESSION['usuario'] = $response_login['data']['username'];
                $_SESSION['rol']     = $response_login['data']['rol_usuario'];
                $_SESSION['image']   = $response_login['data']['imagen_url'] ?? null;
            }
        } catch (Exception $e) {
            error_log('signIn: ' . $e->getMessage());
            $response_login = ['error' => 'Error interno', 'status' => false];
        }

        echo json_encode($response_login);
    }

    public function signUp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'método no permitido']);
            return;
        }

        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['error' => 'token CSRF inválido']);
            return;
        }

        $nombre   = trim($_POST['name'] ?? '');
        $correo   = trim($_POST['correo'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($nombre === '' || $correo === '' || $password === '') {
            echo json_encode(['error' => 'Todos los campos son obligatorios!']);
            return;
        }
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 50) {
            echo json_encode(['error' => 'El nombre debe tener entre 3 y 50 caracteres.']);
            return;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'El correo no es válido.']);
            return;
        }
        if (mb_strlen($password) < 8) {
            echo json_encode(['error' => 'La contraseña debe tener al menos 8 caracteres.']);
            return;
        }

        $userRegister = new User();
        $userRegister->setUsername($nombre);
        $userRegister->setEmail($correo);
        $userRegister->setPassword($password);

        echo json_encode($userRegister->registrar());
    }

    public function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->redirect('/App/acceso');
        exit;
    }

    public function enviarCambioPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'método no permitido']);
            return;
        }
        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['error' => 'token CSRF inválido']);
            return;
        }

        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'correo inválido']);
            return;
        }

        try {
            $usuario = new User();
            $usuario->setEmail($email);

            $key = $usuario->recover();
            // Respuesta genérica siempre (para no filtrar si el correo existe o no).
            if ($key !== null) {
                $link = BASE_URL . '/App/cambioPass?key=' . urlencode($key);
                $html = '<p>Para restablecer tu contraseña, haz clic en el siguiente enlace (válido 30 minutos):</p>'
                      . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Restablecer contraseña</a></p>';
                $this->mail->sendEmail($email, 'Recuperación de contraseña', $html);
            }
        } catch (Exception $th) {
            error_log('recover: ' . $th->getMessage());
        }

        echo json_encode(['message' => 'Si el correo existe, recibirás un enlace.']);
    }

    public function cambioPass()
    {
        $user = new User();
        if (!isset($_GET['key'])) {
            $this->redirect('/App/acceso');
            return;
        }
        $key = (string) $_GET['key'];
        if ($user->validateKey($key)) {
            $user_id = $user->getId($key);
            include_once 'views/app/cambiopassword.php';
        } else {
            $this->redirect('/App/acceso');
        }
    }

    public function UpdatePass()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['response' => 'método no permitido', 'status' => false]);
            return;
        }
        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['response' => 'token CSRF inválido', 'status' => false]);
            return;
        }

        $id_user         = (int) ($_POST['id_user'] ?? 0);
        $password_cambio = trim($_POST['pass'] ?? '');

        if ($id_user <= 0 || mb_strlen($password_cambio) < 8) {
            echo json_encode(['response' => 'datos inválidos', 'status' => false]);
            return;
        }

        $user = new User();
        $user->setId($id_user);
        $user->setPassword($password_cambio);
        $update = $user->UpdatePass();

        if (!empty($update['status'])) {
            echo json_encode(['response' => 'correcto', 'status' => true]);
        } else {
            echo json_encode(['response' => 'error', 'status' => false]);
        }
    }
}
