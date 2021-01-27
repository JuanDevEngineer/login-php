<?php

use function PHPSTORM_META\type;

require_once 'model/UserGestor.php';

class GestorController extends SesionController {


    public function __construct() {}

    public function getName() {
        $usuarios = new UserGestor();
        $user = $usuarios->getName();
        echo json_encode($user);
    }

    public function getUser()
    {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(isset($_POST['id'])) {
                $id = $_POST['id'];
                $user = new UserGestor();
                $data = $user->getUser($id);
                echo json_encode($data);
            } else {
                return array(
                    'error' => "error"
                );
            }
        } else {
            return array(
                'error' => "error en el metodo de envio"
            );
        }
        
    }

    public function getUsers() {
        $id = "";
        $estado = "";

        if(isset($_POST['id'])) {
            $id = $_POST['id'];
        }
        if(isset($_POST['estado'])) {
            $estado = $_POST['estado'];
        }

        $usuarios = new UserGestor();
        $user = $usuarios->getUsers($id, $estado);
        echo json_encode($user);
    }

    public function getRol() 
    {
        $usuarios = new UserGestor();
        $user = $usuarios->getRol();
        echo json_encode($user);
    }

    public function updataState() 
    {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(isset($_POST['id']) && isset($_POST['estado'])) {
                $id = $_POST['id'];
                $estado = $_POST['estado'];
                $user = new UserGestor();
                $data = $user->updataState($id, $estado);
                echo json_encode($data);
            } else {
                return array(
                    'error' => "en los datos de envio"
                );
            }
        } else {
            return array(
                'error' => "error en el metodo de envio"
            );
        }
    }

    public function uploadImageProfile() 
    {   
        $path = "assets/";
        $type[] = ['image/gif', 'image/jpeg', 'image/jpg', 'image/png'];


        for ($i=0; $i < count($type); $i++) { 
            if($_FILES['profile']['type'] === $type[$i]) {
                return true;
            }

            return false;

            print_r($_FILES);
        }

        // $nombreImage = $_FILES['image-rol']['name'];
        

        // if($_SERVER['REQUEST_METHOD'] === 'POST') {
            
        // }
    } 

}