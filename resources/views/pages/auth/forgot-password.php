<?php
/** @var \App\Presentation\View\ViewRenderer $view @var string $baseUrl */
$view->layout('public');

$body = $view->partial('partials/forms/forgot-password', ['baseUrl' => $baseUrl, 'csrf' => $csrf]);

$footer = '
    <div class="d-flex justify-content-center links">
        <a href="' . e($baseUrl) . '/login">Volver al login</a>
    </div>';

echo $view->partial('components/auth-card', [
    'title'  => 'Recuperar contraseña',
    'body'   => $body,
    'footer' => $footer,
]);
