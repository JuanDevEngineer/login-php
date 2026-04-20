<?php

require_once './config/DataBase.php';

class Model
{
    protected $con;

    public function __construct()
    {
        $this->con = new Database();
    }

    protected function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    protected function generateRandomString($length = 50)
    {
        // random_bytes() es criptográficamente seguro. bin2hex duplica la longitud,
        // por eso pedimos la mitad de bytes.
        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }

    public function validate($dato, $value)
    {
        switch ($dato) {
            case 'email':
                $sql = "SELECT email FROM usuario WHERE email = :email";
                $stmt = $this->con->getConnection()->prepare($sql);
                $stmt->bindParam(':email', $value, PDO::PARAM_STR);
                break;
            
            case 'username':
                $sql = "SELECT username FROM usuario WHERE username = :username";
                $stmt = $this->con->getConnection()->prepare($sql);
                $stmt->bindParam(':username', $value, PDO::PARAM_STR);
                break;
            
            default:
                return false;
                break;
        }

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}