<form id="form-recover" method="post" action="<?= e($baseUrl) ?>/password/forgot" novalidate>
    <?= $csrf->field() ?>

    <p class="text-light small">
        Ingresá tu correo y te enviamos un enlace para crear una contraseña nueva.
    </p>

    <?= $view->partial('components/input-group', [
        'name'         => 'email',
        'type'         => 'email',
        'icon'         => 'fas fa-envelope',
        'placeholder'  => 'Correo electrónico',
        'required'     => true,
        'autocomplete' => 'email',
    ]) ?>

    <div class="form-group">
        <input type="submit" value="Enviar enlace" class="btn btn-block mt-3 login_btn">
    </div>
</form>
