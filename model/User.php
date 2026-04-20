<?php

// require_once '../config/DataBase.php';
require_once 'Model.php';

class User extends Model
{
  
  private $username;
  private $email;
  private $password;
  private $id;

  public function __construct()
  {
    parent::__construct();
  }

  function getUsername()
  {
    return $this->username;
  }

  function setUsername($username)
  {
    $this->username = $username;
  }

  function getEmail()
  {
    return $this->email;
  }

  function setEmail($email)
  {
    $this->email = $email;
  }

  function setPassword($password)
  {
    $this->password = $password;
  }

  function getPassword()
  {
    return $this->password;
  }

  function setId($id)
  {
    $this->id = $id;
  }

  function getIdUser()
  {
    return $this->id;
  }

  /**
   * Comprueba si ya existe un usuario con ese username (exacto, case-sensitive).
   */
  private function validateUser($user)
  {
    $sql = "SELECT 1 FROM usuario WHERE username = BINARY :username LIMIT 1";
    $stmt = $this->con->getConnection()->prepare($sql);
    $stmt->bindValue(":username", $user, PDO::PARAM_STR);

    if ($stmt->execute()) {
      return $stmt->rowCount() > 0;
    }
    return false;
  }

  public function validateKey($key)
  {
    // Buscamos cualquier token que empiece por la key (prefijo)
    // y dentro comprobamos la expiración.
    $sql  = "SELECT id_usuario, recover FROM usuario WHERE recover LIKE :prefix";
    $stmt = $this->con->getConnection()->prepare($sql);
    $prefix = $key . '|%';
    $stmt->bindValue(":prefix", $prefix, PDO::PARAM_STR);

    if (!$stmt->execute() || $stmt->rowCount() === 0) {
      return false;
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $parts = explode('|', $row['recover']);
    if (count($parts) !== 2) {
      return false;
    }
    [$storedKey, $expires] = $parts;
    if (!hash_equals($storedKey, $key)) {
      return false;
    }
    return time() < (int) $expires;
  }

  public function getId($key)
  {
    $sql  = "SELECT id_usuario FROM usuario WHERE recover LIKE :prefix";
    $stmt = $this->con->getConnection()->prepare($sql);
    $stmt->bindValue(":prefix", $key . '|%', PDO::PARAM_STR);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return ['error' => 'clave no encontrada'];
  }

  public function getData($correo)
  {
    $response = array();

    $sql = "SELECT id_usuario FROM usuario WHERE email = :email";
    $stmt = $this->con->getConnection()->prepare($sql);
    $stmt->bindParam(":email", $correo, PDO::PARAM_STR);

    if ($stmt->execute()) {
      error_log("ejecucion->" . $stmt->execute());

      if ($stmt->rowCount() > 0) {
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['data'] = $data;
        $response['status'] = true;
        return $response;
      } else {
        $response['error'] = "error, usuario o password incorrecto";
        $response['status'] = false;
        return $response;
      }
    }
  }

  public function login()
  {

    // obtenemos los valores
    $username_usuario = $this->getUsername();
    $password_usuario = $this->getPassword();
    $response = array();

    //validamos el usuario
    $sql  = "SELECT u.*, r.nombre as 'rol_usuario' FROM usuario u
             INNER JOIN rol r ON r.id_rol = u.rol_id
             WHERE u.username = ? ";

    $stmt = $this->con->getConnection()->prepare($sql);

    $stmt->bindParam(1, $username_usuario, PDO::PARAM_STR);

    // validar la ejecucion
    if ($stmt->execute()) {

      if ($stmt->rowCount() > 0) {
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($password_usuario, $data['password'])) {
          $response['data'] = $data;
          $response['status'] = true;
          return $response;
        } else {
          $response['error'] = "Contraseña incorrecta!";
          $response['status'] = false;
          return $response;
        }
      } else {
        $response['error'] = "usuario o password incorrecto!";
        $response['status'] = false;
        return $response;
      }
    } else {
      $response['error'] = "El al hacer la ejecucion!";
      $response['status'] = false;
      return $response;
    }
  }

  public function registrar()
  {
    $response = array();
    try {
      if (!$this->validateUser($this->getUsername())) {

        $password_hash = $this->hashPassword($this->getPassword());
        $username = $this->getUsername();
        $email    = $this->getEmail();

        $sql = "INSERT INTO `usuario` (username, email, password, rol_id, registro)
                VALUES(:username, :email, :password, 2, CURDATE())";

        $stmt = $this->con->getConnection()->prepare($sql);
        $stmt->bindValue(":username", $username, PDO::PARAM_STR);
        $stmt->bindValue(":email",    $email,    PDO::PARAM_STR);
        $stmt->bindValue(":password", $password_hash, PDO::PARAM_STR);

        if ($stmt->execute()) {
          $response['success'] = "usuario registrado correctamente!";
          return $response;
        }
        $response['error'] = "Error registrando usuario.";
        return $response;
      } else {
        $response['error'] = "El usuario ya existe, intenta con otro diferente!";
        return $response;
      }
    } catch (PDOException $e) {
      error_log("registrar error: " . $e->getMessage());
      return ['error' => 'Error registrando usuario.'];
    }
  }

  public function recover()
  {
    $email = $this->getEmail();
    $usuario = $this->getData($email);

    if ($usuario && !empty($usuario['status'])) {
      $key = $this->generateRandomString();
      // Almacenamos key|expira_en_unix. Expiración: 30 min.
      $expires = time() + (30 * 60);
      $storedKey = $key . '|' . $expires;

      $sql  = "UPDATE usuario SET recover = :key WHERE id_usuario = :id";
      $stmt = $this->con->getConnection()->prepare($sql);
      $stmt->bindValue(":key", $storedKey, PDO::PARAM_STR);
      $stmt->bindValue(":id",  (int) $usuario['data']['id_usuario'], PDO::PARAM_INT);

      if ($stmt->execute()) {
        return $key;
      }
      return null;
    }
    return null;
  }

  public function UpdatePass()
  {
    try {
      $id_user = (int) $this->getIdUser();
      $password_new = $this->hashPassword($this->getPassword());

      // Invalidar recover al cambiar la clave (uso único).
      $sql = "UPDATE usuario SET password = :password, recover = NULL WHERE id_usuario = :id";

      $stmt = $this->con->getConnection()->prepare($sql);
      $stmt->bindValue(":password", $password_new, PDO::PARAM_STR);
      $stmt->bindValue(":id", $id_user, PDO::PARAM_INT);

      if ($stmt->execute()) {
        return [
          'response' => 'correcto',
          'status' => true
        ];
      } else {
        return [
          'response' => 'error',
          'status' => false
        ];
      }
    } catch (PDOException $e) {
      error_log("UpdatePass error: " . $e->getMessage());
      return ["error" => 'Error actualizando password.', 'status' => false];
    }
  }
}
