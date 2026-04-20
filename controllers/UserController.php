<?php

require_once 'model/User.php';

class UserController extends SesionController
{

    protected $view;

    public function __construct()
    {
        $this->view = new View();
    }

    public function dashboard()
    {
        if ($this->isUser()) {
            $this->view->render('usuarios/editar');
        } else {
            $this->redirect('/App/acceso');
        }
    }
}
