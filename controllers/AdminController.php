<?php

class AdminController extends SesionController
{
    protected $view;

    public function __construct()
    {
        $this->view = new View();
    }

    public function inicio()
    {
        $this->requireAuth();
        $this->view->render('usuarios/index');
    }

    /**
     * Vista de gestión de usuarios: sólo admin.
     */
    public function gestor()
    {
        $this->requireAdmin();
        $this->view->render('usuarios/gestor');
    }

    public function perfil()
    {
        $this->requireAuth();
        $this->view->render('usuarios/perfil');
    }

    public function creacionproductos()
    {
        $this->requireAdmin();
        $this->view->render('productos/creacion');
    }

    public function productos()
    {
        $this->requireAuth();
        $this->view->render('productos/gestor');
    }
}
