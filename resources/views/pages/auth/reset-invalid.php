<?php
/** @var string $message */
$view->layout('public');

$body = '<p class="text-light">' . e($message) . '</p>
    <a href="' . e($baseUrl) . '/password/forgot" class="btn btn-block login_btn">
        Pedir un enlace nuevo
    </a>';

echo $view->partial('components/auth-card', [
    'title' => 'Enlace inválido',
    'body'  => $body,
]);
