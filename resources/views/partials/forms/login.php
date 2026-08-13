<?php /** @var \App\Infrastructure\Security\CsrfGuard $csrf */ ?>
<form id="form-login" method="post" action="<?= e($baseUrl) ?>/login" novalidate>
    <?= $csrf->field() ?>

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
        'placeholder'  => 'Contraseña',
        'required'     => true,
        'autocomplete' => 'current-password',
    ]) ?>

    <div class="row align-items-center remember ml-1">
        <input type="checkbox" id="remember-me">
        <label for="remember-me" class="mb-0 ml-2">Recordar mi usuario</label>
    </div>

    <div class="form-group">
        <input type="submit" value="Entrar" class="btn btn-block mt-3 login_btn">
    </div>
</form>
