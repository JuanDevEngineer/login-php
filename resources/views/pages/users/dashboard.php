<?php
/** @var \App\Presentation\View\ViewRenderer $view @var \App\Application\Dto\AuthenticatedUser $authUser */
$view->layout('dashboard');
?>
<div class="row">
    <div class="col-12">
        <?= $view->partial('components/card', [
            'title' => 'Bienvenido, ' . $authUser->username,
            'body'  => '<p class="mb-0">Sesión iniciada como <strong>'
                       . e($authUser->email) . '</strong> con rol <code>'
                       . e($authUser->role) . '</code>.</p>',
        ]) ?>
    </div>
</div>
