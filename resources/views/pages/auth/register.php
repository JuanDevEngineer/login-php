<?php
/** @var \App\Presentation\View\ViewRenderer $view @var string $baseUrl */
$view->layout('public');

$body = $view->partial('partials/forms/register', ['baseUrl' => $baseUrl, 'csrf' => $csrf]);

$footer = '
    <div class="d-flex justify-content-center">
        <a href="' . e($baseUrl) . '/login">Volver al login</a>
    </div>';

echo $view->partial('components/auth-card', [
    'title'  => 'Crear cuenta',
    'body'   => $body,
    'footer' => $footer,
]);
