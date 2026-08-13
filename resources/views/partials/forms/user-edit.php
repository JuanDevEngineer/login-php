<form id="form-editar-usuario" method="post" action="<?= e($baseUrl) ?>/api/users/update">
    <?= $csrf->field() ?>
    <input type="hidden" name="id" id="edit-id">

    <div class="row">
        <?= $view->partial('components/input-group', [
            'name'        => 'username',
            'id'          => 'edit-username',
            'icon'        => 'fas fa-user',
            'placeholder' => 'Usuario',
            'wrapper'     => 'col-md-6',
            'required'    => true,
        ]) ?>

        <?= $view->partial('components/input-group', [
            'name'        => 'email',
            'id'          => 'edit-email',
            'type'        => 'email',
            'icon'        => 'fas fa-envelope',
            'placeholder' => 'Correo',
            'wrapper'     => 'col-md-6',
            'required'    => true,
        ]) ?>
    </div>

    <div class="row">
        <?= $view->partial('components/input-group', [
            'name'        => 'rol_actual',
            'id'          => 'edit-rol-actual',
            'icon'        => 'fas fa-users',
            'placeholder' => 'Rol actual',
            'wrapper'     => 'col-md-6',
            'disabled'    => true,
        ]) ?>

        <div class="input-group form-group col-md-6">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-user-plus"></i></span>
            </div>
            <select name="rol" id="edit-rol" class="form-control" required>
                <option value="">Seleccioná el rol nuevo</option>
            </select>
        </div>
    </div>

    <div class="row">
        <?= $view->partial('components/input-group', [
            'name'        => 'estado_actual',
            'id'          => 'edit-estado',
            'icon'        => 'fas fa-toggle-on',
            'placeholder' => 'Estado',
            'wrapper'     => 'col-md-6',
            'disabled'    => true,
        ]) ?>

        <?= $view->partial('components/input-group', [
            'name'        => 'registro',
            'id'          => 'edit-registro',
            'icon'        => 'far fa-calendar-alt',
            'placeholder' => 'Fecha de creación',
            'wrapper'     => 'col-md-6',
            'disabled'    => true,
        ]) ?>
    </div>

    <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-outline-light">Actualizar</button>
    </div>
</form>
