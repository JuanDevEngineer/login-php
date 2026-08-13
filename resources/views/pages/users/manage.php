<?php
/** Gestor de usuarios (solo admin). */
$view->layout('dashboard');

$filters = $view->partial('partials/forms/user-filters', ['csrf' => $csrf]);

$table = $view->partial('components/data-table', [
    'id'      => 'ud_user',
    'columns' => ['N°', 'Usuario', 'Correo', 'Rol', 'Estado', 'Acciones'],
]);

$createForm = $view->partial('partials/forms/user-create', ['baseUrl' => $baseUrl, 'csrf' => $csrf]);
$editForm   = $view->partial('partials/forms/user-edit',   ['baseUrl' => $baseUrl, 'csrf' => $csrf]);

$nuevoBoton = '<button type="button" class="btn btn-sm btn-success" data-action="nuevo-usuario">
                   <i class="fas fa-user-plus mr-1"></i> Nuevo usuario
               </button>';
?>
<div class="row">
    <div class="col-12">
        <?= $view->partial('components/card', [
            'title' => 'Filtros',
            'tools' => true,
            'body'  => $filters,
        ]) ?>
    </div>
</div>

<div class="row usuario">
    <div class="col-12">
        <?= $view->partial('components/card', [
            'title'   => 'Usuarios',
            'actions' => $nuevoBoton,
            'body'    => $table,
        ]) ?>
    </div>
</div>

<?= $view->partial('components/modal', [
    'id'    => 'modal-crear',
    'title' => 'Nuevo usuario',
    'body'  => $createForm,
]) ?>

<?= $view->partial('components/modal', [
    'id'    => 'modal-editar',
    'title' => 'Editar usuario',
    'body'  => $editForm,
]) ?>
