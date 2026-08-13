<?php
/** @var \App\Presentation\View\ViewRenderer $view */
$view->layout('public');

$detail = $message ?? 'Tu sesión expiró o el formulario perdió validez. Recargá la página e intentá de nuevo.';

echo $view->partial('components/auth-card', [
    'title'  => 'Sesión expirada',
    'body'   => '<p class="text-light mb-0">' . e($detail) . '</p>',
    'footer' => '<div class="d-flex justify-content-center">
                    <a href="' . e($baseUrl) . '/">Volver al inicio</a>
                 </div>',
]);
