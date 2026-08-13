<?php
/** @var \App\Presentation\View\ViewRenderer $view */
$view->layout('public');

$detail = $message ?? 'No tenés permiso para ver esta página.';

echo $view->partial('components/auth-card', [
    'title'  => 'Acceso denegado',
    'body'   => '<p class="text-light mb-0">' . e($detail) . '</p>',
    'footer' => '<div class="d-flex justify-content-center">
                    <a href="' . e($baseUrl) . '/">Volver al inicio</a>
                 </div>',
]);
