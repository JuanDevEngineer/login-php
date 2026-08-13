<form id="form-registro" method="post" action="<?= e($baseUrl) ?>/register" novalidate>
    <?= $csrf->field() ?>

    <?= $view->partial('components/input-group', [
        'name'         => 'email',
        'type'         => 'email',
        'icon'         => 'fas fa-envelope',
        'placeholder'  => 'Correo electrónico',
        'required'     => true,
        'autocomplete' => 'email',
    ]) ?>

    <?= $view->partial('components/input-group', [
        'name'         => 'username',
        'icon'         => 'fas fa-user',
        'placeholder'  => 'Usuario',
        'required'     => true,
        'autocomplete' => 'username',
    ]) ?>

    <?= $view->partial('components/input-group', [
        'name'         => 'password',
        'type'         => 'password',
        'icon'         => 'fas fa-key',
        'placeholder'  => 'Contraseña (mínimo 8 caracteres)',
        'required'     => true,
        'autocomplete' => 'new-password',
    ]) ?>

    <div class="form-group">
        <input type="submit" value="Registrarme" class="btn btn-block mt-3 login_btn">
    </div>
</form>
