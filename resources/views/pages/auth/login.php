<?php
/** @var \App\Presentation\View\ViewRenderer $view @var string $baseUrl */
$view->layout('public');

$body = $view->partial('partials/forms/login', ['baseUrl' => $baseUrl, 'csrf' => $csrf]);

$footer = '
    <div class="d-flex justify-content-center links">
        ¿Aún no tenés cuenta?<a href="' . e($baseUrl) . '/register">&nbsp;Registrate</a>
    </div>
    <div class="d-flex justify-content-center">
        <a href="' . e($baseUrl) . '/password/forgot">¿Olvidaste la contraseña?</a>
    </div>';

echo $view->partial('components/auth-card', [
    'title'  => 'Iniciar sesión',
    'body'   => $body,
    'footer' => $footer,
]);
