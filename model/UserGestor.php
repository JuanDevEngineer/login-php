<?php

require_once 'Model.php';
// require_once '../config/DataBase.php';

class UserGestor extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getName()
    {
        try {
            $sql = "SELECT u.id_usuario, u.username FROM usuario u";

            $stmt = $this->con->getConnection()->prepare($sql);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    return array();
                }
            }
        } catch (PDOException $th) {
            return array(
                'error' => $th->getMessage()
            );
        }
    }

    public function getUser($id)
    {
        try {
            $sql = "SELECT
                    u.id_usuario,
                    u.username,
                    u.email,
                    u.rol_id,
                    CASE
                        WHEN u.estado = 1 THEN 'activo'
                        ELSE 'inactivo'
                    END as estado,
                    r.nombre as 'rol_usuario',
                    u.registro
                    FROM usuario u
                    INNER JOIN rol r ON r.id_rol = u.rol_id
                    WHERE u.id_usuario = :id";

            $stmt = $this->con->getConnection()->prepare($sql);
            $stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    return array();
                }
            }
        } catch (PDOException $th) {
            error_log("getUser error: " . $th->getMessage());
            return array(
                'error' => 'Error consultando usuario.'
            );
        }
    }

    public function getUsers($id, $estado)
    {
        try {
            $condiciones = [];
            $params = [];

            if ($id !== "" && $id !== null) {
                $condiciones[] = "u.id_usuario = :id";
                $params[':id'] = (int) $id;
            }

            if ($estado !== "" && $estado !== null) {
                $condiciones[] = "u.estado = :estado";
                $params[':estado'] = (int) $estado;
            }

            $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';

            $sql = "SELECT
                    u.id_usuario,
                    u.username,
                    u.email,
                    u.rol_id,
                    u.estado,
                    r.nombre as 'rol_usuario'
                    FROM usuario u
                    INNER JOIN rol r ON r.id_rol = u.rol_id" . $where;

            $stmt = $this->con->getConnection()->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    return array();
                }
            }
        } catch (PDOException $th) {
            error_log("getUsers error: " . $th->getMessage());
            return array(
                'error' => 'Error consultando usuarios.'
            );
        }
    }

    public function updateUser($data)
    {
        try {
            $sql = "UPDATE usuario
                    SET username = :username,
                        email = :email,
                        rol_id = :rol_id
                    WHERE id_usuario = :id";

            $stmt = $this->con->getConnection()->prepare($sql);
            $stmt->bindValue(":username", $data['username'], PDO::PARAM_STR);
            $stmt->bindValue(":email", $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(":rol_id", (int) $data['rol'], PDO::PARAM_INT);
            $stmt->bindValue(":id", (int) $data['id_usuario'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return array(
                        'success' => true,
                        'msg' => 'usuario editado'
                    );
                } else {
                    return array(
                        'success' => false,
                        'msg' => 'sin cambios'
                    );
                }
            }
        } catch (PDOException $th) {
            error_log("updateUser error: " . $th->getMessage());
            return array(
                'success' => false,
                'error' => 'Error actualizando usuario.'
            );
        }
    }

    public function getRol() 
    {
        try {
            $sql = "SELECT
                    r.id_rol,
                    r.nombre as 'rol_usuario' 
                    FROM rol r";

            // echo $sql;exit;
            $stmt = $this->con->getConnection()->prepare($sql);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    return array();
                }
            }
        } catch (PDOException $th) {
            return array(
                'error' => $th->getMessage()
            );
        }
    }

    public function updataState($id, $estado)
    {
        try {
            $estadoInt = (int) $estado;
            if ($estadoInt !== 0 && $estadoInt !== 1) {
                return array('error' => 'estado inválido');
            }

            // Invertir el estado actual
            $nuevo = $estadoInt === 0 ? 1 : 0;

            $sql = "UPDATE usuario SET estado = :nuevo WHERE id_usuario = :id";
            $stmt = $this->con->getConnection()->prepare($sql);
            $stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
            $stmt->bindValue(":nuevo", $nuevo, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return array('success' => true);
                } else {
                    return array('error' => 'sin cambios');
                }
            }
        } catch (PDOException $th) {
            error_log("updataState error: " . $th->getMessage());
            return array(
                'error' => 'Error actualizando estado.'
            );
        }
    }

    public function uploadNameImage($data)
    {
        try {
            $sql = "UPDATE usuario
                    SET imagen_url = :imagen
                    WHERE id_usuario = :id";

            $stmt = $this->con->getConnection()->prepare($sql);
            $stmt->bindValue(":imagen", $data['imagen_url'], PDO::PARAM_STR);
            $stmt->bindValue(":id", (int) $data['id_usuario'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return array(
                        'success' => true,
                        'message' => 'vuelve a iniciar sesion!'
                    );
                } else {
                    return array('error' => 'sin cambios');
                }
            }
        } catch (PDOException $th) {
            error_log("uploadNameImage error: " . $th->getMessage());
            return array(
                'error' => 'Error guardando la imagen.'
            );
        }
    }
}