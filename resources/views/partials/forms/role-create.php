<?php
/**
 * Alta de rol.
 *
 * @var \App\Presentation\View\ViewRenderer $view
 * @var \App\Infrastructure\Security\CsrfGuard $csrf
 * @var string $baseUrl
 */
?>
<form id="form-crear-rol" method="post" action="<?= e($baseUrl) ?>/api/roles/create" novalidate>
    <?= $csrf->field() ?>

    <?= $view->partial('components/input-group', [
        'name'         => 'nombre',
        'id'           => 'create-rol-nombre',
        'icon'         => 'fas fa-user-tag',
        'placeholder'  => 'Nombre del rol',
        'required'     => true,
        'autocomplete' => 'off',
    ]) ?>

    <small class="text-muted d-block">
        Se guarda en mayúsculas. Solo letras sin tilde, números y guion bajo,
        empezando por una letra. Por ejemplo: <code>ROL_VENTAS</code>.
    </small>

    <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-outline-success">Crear rol</button>
    </div>
</form>
