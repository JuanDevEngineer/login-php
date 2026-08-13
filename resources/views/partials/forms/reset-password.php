<form id="form-password" method="post" action="<?= e($baseUrl) ?>/password/reset" novalidate>
    <?= $csrf->field() ?>

    <!--
        Viaja el token, no el id del usuario: el servidor vuelve a validarlo
        antes de cambiar nada, así manipular estos campos no sirve de nada.
    -->
    <input type="hidden" name="selector" value="<?= e($selector) ?>">
    <input type="hidden" name="verifier" value="<?= e($verifier) ?>">

    <?= $view->partial('components/input-group', [
        'name'         => 'password',
        'type'         => 'password',
        'icon'         => 'fas fa-key',
        'placeholder'  => 'Nueva contraseña (mínimo 8)',
        'required'     => true,
        'autocomplete' => 'new-password',
    ]) ?>

    <?= $view->partial('components/input-group', [
        'name'         => 'password_confirm',
        'type'         => 'password',
        'icon'         => 'fas fa-key',
        'placeholder'  => 'Repetir contraseña',
        'required'     => true,
        'autocomplete' => 'new-password',
    ]) ?>

    <div class="form-group">
        <input type="submit" value="Cambiar contraseña" class="btn btn-block mt-3 login_btn">
    </div>
</form>
