<?php
/** @var string $selector @var string $verifier */
$view->layout('public');

$body = $view->partial('partials/forms/reset-password', [
    'baseUrl'  => $baseUrl,
    'csrf'     => $csrf,
    'selector' => $selector,
    'verifier' => $verifier,
]);

echo $view->partial('components/auth-card', [
    'title' => 'Nueva contraseña',
    'body'  => $body,
]);
