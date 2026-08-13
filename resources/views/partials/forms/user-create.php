<?php
/**
 * Formulario de alta de usuario (modal del gestor).
 *
 * Va como multipart porque incluye el avatar. El JS lo envía con FormData, así
 * que basta con que los campos tengan `name`.
 *
 * @var \App\Presentation\View\ViewRenderer $view
 * @var \App\Infrastructure\Security\CsrfGuard $csrf
 * @var string $baseUrl
 */
?>
<form id="form-crear-usuario" method="post" enctype="multipart/form-data"
      action="<?= e($baseUrl) ?>/api/users/create" novalidate>
    <?= $csrf->field() ?>

    <div class="row">
        <?= $view->partial('components/input-group', [
            'name'         => 'username',
            'id'           => 'create-username',
            'icon'         => 'fas fa-user',
            'placeholder'  => 'Usuario',
            'wrapper'      => 'col-md-6',
            'required'     => true,
            'autocomplete' => 'off',
        ]) ?>

        <?= $view->partial('components/input-group', [
            'name'         => 'email',
            'id'           => 'create-email',
            'type'         => 'email',
            'icon'         => 'fas fa-envelope',
            'placeholder'  => 'Correo electrónico',
            'wrapper'      => 'col-md-6',
            'required'     => true,
            'autocomplete' => 'off',
        ]) ?>
    </div>

    <div class="row">
        <?= $view->partial('components/input-group', [
            'name'         => 'password',
            'id'           => 'create-password',
            'type'         => 'password',
            'icon'         => 'fas fa-key',
            'placeholder'  => 'Contraseña (mínimo 8)',
            'wrapper'      => 'col-md-6',
            'required'     => true,
            'autocomplete' => 'new-password',
        ]) ?>

        <?= $view->partial('components/input-group', [
            'name'         => 'password_confirm',
            'id'           => 'create-password-confirm',
            'type'         => 'password',
            'icon'         => 'fas fa-key',
            'placeholder'  => 'Repetir contraseña',
            'wrapper'      => 'col-md-6',
            'required'     => true,
            'autocomplete' => 'new-password',
        ]) ?>
    </div>

    <div class="row">
        <div class="input-group form-group col-md-6">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
            </div>
            <select name="rol" id="create-rol" class="form-control" required>
                <option value="">Seleccioná el rol</option>
            </select>
        </div>

        <div class="input-group form-group col-md-6">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
            </div>
            <select name="estado" id="create-estado" class="form-control">
                <option value="1" selected>Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="input-group form-group mb-1">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-camera-retro"></i></span>
                </div>
                <div class="custom-file">
                    <input type="file" name="profile" class="custom-file-input" id="create-avatar"
                           accept="image/png,image/jpeg,image/gif,image/webp">
                    <label class="custom-file-label" for="create-avatar">Foto de perfil (opcional)</label>
                </div>
            </div>
            <small class="text-muted">JPG, PNG, GIF o WebP. Máximo 2 MB.</small>
        </div>
    </div>

    <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-outline-success">Crear usuario</button>
    </div>
</form>
