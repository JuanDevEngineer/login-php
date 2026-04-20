<?php

require_once 'model/UserGestor.php';

class GestorController extends SesionController
{
    public function __construct() {}

    /**
     * Lista usuarios (para poblar el select en el formulario).
     * Requiere sesión de admin.
     */
    public function getName()
    {
        $this->requireAdminJson();
        $usuarios = new UserGestor();
        $user = $usuarios->getName();
        echo json_encode($user);
    }

    public function getUser()
    {
        $this->requireAdminJson();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'msg' => 'método no permitido']);
            return;
        }
        $this->verifyCsrfJson();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'msg' => 'id inválido']);
            return;
        }
        $user = new UserGestor();
        $data = $user->getUser($id);
        echo json_encode($data);
    }

    public function getUsers()
    {
        $this->requireAdminJson();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'msg' => 'método no permitido']);
            return;
        }
        $this->verifyCsrfJson();

        $id     = isset($_POST['id'])     ? trim((string) $_POST['id'])     : '';
        $estado = isset($_POST['estado']) ? trim((string) $_POST['estado']) : '';

        $usuarios = new UserGestor();
        $user = $usuarios->getUsers($id, $estado);
        echo json_encode($user);
    }

    public function getRol()
    {
        $this->requireAdminJson();
        $usuarios = new UserGestor();
        $user = $usuarios->getRol();
        echo json_encode($user);
    }

    public function updateUser()
    {
        $this->requireAdminJson();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'msg' => 'método no permitido']);
            return;
        }
        $this->verifyCsrfJson();

        $data = [
            'id_usuario' => (int) ($_POST['id_usuario'] ?? 0),
            'username'   => trim((string) ($_POST['username'] ?? '')),
            'email'      => trim((string) ($_POST['email'] ?? '')),
            'rol'        => trim((string) ($_POST['rol'] ?? '')),
        ];

        if ($data['id_usuario'] <= 0 || $data['username'] === '' || $data['email'] === '' || $data['rol'] === '') {
            echo json_encode(['success' => false, 'msg' => 'campos incompletos']);
            return;
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'msg' => 'correo inválido']);
            return;
        }

        $gestor = new UserGestor();
        $response = $gestor->updateUser($data);

        echo json_encode([
            'success' => !empty($response['success']),
            'msg'     => !empty($response['success']) ? 'correcto' : 'error',
        ]);
    }

    public function updataState()
    {
        $this->requireAdminJson();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'msg' => 'método no permitido']);
            return;
        }
        $this->verifyCsrfJson();

        if (!isset($_POST['id'], $_POST['estado'])) {
            echo json_encode(['success' => false, 'msg' => 'datos incompletos']);
            return;
        }
        $id     = (int) $_POST['id'];
        $estado = (int) $_POST['estado'];
        if ($id <= 0) {
            echo json_encode(['success' => false, 'msg' => 'id inválido']);
            return;
        }
        $user = new UserGestor();
        $data = $user->updataState($id, $estado);
        echo json_encode($data);
    }

    public function uploadImageProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'msg' => 'método no permitido']);
            return;
        }

        $this->requireAuthJson();
        $this->verifyCsrfJson();

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            echo json_encode(['success' => false, 'msg' => 'id inválido']);
            return;
        }

        // Sólo el propio usuario puede subir su imagen (o admin).
        if (!isset($_SESSION['id_k']) || ((int) $_SESSION['id_k'] !== $idUsuario && ($_SESSION['rol'] ?? '') !== 'ROL_ADMIN')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => 'no autorizado']);
            return;
        }

        if (empty($_FILES['profile']) || $_FILES['profile']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'msg' => 'error al subir el archivo']);
            return;
        }

        $tmp_name = $_FILES['profile']['tmp_name'];
        $size     = (int) $_FILES['profile']['size'];

        // Detectar MIME real (no fiable el que manda el navegador).
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmp_name);

        if (!$this->validateFormatImage($mime)) {
            echo json_encode(['success' => false, 'msg' => 'tipo de archivo no permitido']);
            return;
        }
        if (!$this->validateSize($size)) {
            echo json_encode(['success' => false, 'msg' => 'archivo demasiado grande']);
            return;
        }

        // Whitelist de extensiones por MIME real.
        $extPorMime = [
            'image/gif'  => 'gif',
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
        ];
        $ext = $extPorMime[$mime] ?? null;
        if ($ext === null) {
            echo json_encode(['success' => false, 'msg' => 'extensión no permitida']);
            return;
        }

        $path = __DIR__ . '/../assets/uploads/';
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        $nombre_guardar = bin2hex(random_bytes(16)) . '.' . $ext;

        if (!move_uploaded_file($tmp_name, $path . $nombre_guardar)) {
            echo json_encode(['success' => false, 'msg' => 'no se pudo guardar el archivo']);
            return;
        }

        $nombre_imagen = BASE_URL . '/assets/uploads/' . $nombre_guardar;

        $user = new UserGestor();
        $data = $user->uploadNameImage([
            'id_usuario' => $idUsuario,
            'imagen_url' => $nombre_imagen,
        ]);
        echo json_encode($data);
    }
}
