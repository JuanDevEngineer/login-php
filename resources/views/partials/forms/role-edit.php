<?php
/**
 * Edición de rol. Solo llegan acá los roles que no son de sistema: la tabla
 * desactiva el botón para los protegidos y el dominio lo rechaza igual si
 * alguien fuerza la petición.
 *
 * @var \App\Presentation\View\ViewRenderer $view
 * @var \App\Infrastructure\Security\CsrfGuard $csrf
 * @var string $baseUrl
 */
?>
<form id="form-editar-rol" method="post" action="<?= e($baseUrl) ?>/api/roles/update" novalidate>
    <?= $csrf->field() ?>
    <input type="hidden" name="id" id="edit-rol-id">

    <?= $view->partial('components/input-group', [
        'name'         => 'nombre',
        'id'           => 'edit-rol-nombre',
        'icon'         => 'fas fa-user-tag',
        'placeholder'  => 'Nombre del rol',
        'required'     => true,
        'autocomplete' => 'off',
    ]) ?>

    <small class="text-muted d-block">
        Renombrar un rol no cambia los permisos de quienes lo tienen asignado.
    </small>

    <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-outline-light">Guardar</button>
    </div>
</form>
