<?php
/** Gestor de roles (solo admin). */
$view->layout('dashboard');

$table = $view->partial('components/data-table', [
    'id'      => 'tabla-roles',
    'columns' => ['N°', 'Nombre', 'Usuarios', 'Tipo', 'Acciones'],
]);

$createForm = $view->partial('partials/forms/role-create', ['baseUrl' => $baseUrl, 'csrf' => $csrf]);
$editForm   = $view->partial('partials/forms/role-edit',   ['baseUrl' => $baseUrl, 'csrf' => $csrf]);

$nuevoBoton = '<button type="button" class="btn btn-sm btn-success" data-action="nuevo-rol">
                   <i class="fas fa-plus mr-1"></i> Nuevo rol
               </button>';
?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-secondary">
            <i class="fas fa-info-circle mr-1"></i>
            Los roles marcados como <strong>del sistema</strong> no se pueden renombrar
            ni eliminar: la aplicación depende de ellos para saber quién es
            administrador y qué rol asignar a quien se registra.
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?= $view->partial('components/card', [
            'title'   => 'Roles',
            'actions' => $nuevoBoton,
            'body'    => $table,
        ]) ?>
    </div>
</div>

<?= $view->partial('components/modal', [
    'id'    => 'modal-crear-rol',
    'title' => 'Nuevo rol',
    'size'  => 'modal-md',
    'body'  => $createForm,
]) ?>

<?= $view->partial('components/modal', [
    'id'    => 'modal-editar-rol',
    'title' => 'Editar rol',
    'size'  => 'modal-md',
    'body'  => $editForm,
]) ?>
