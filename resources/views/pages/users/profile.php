<?php
/**
 * Perfil del usuario en sesión.
 *
 * El original tenía tres <form id="form-login"> repetidos en la misma página
 * (ids duplicados, HTML inválido) y un formulario de cambio de contraseña con
 * inputs sin name que no enviaba nada. Acá queda un solo formulario real: la
 * subida de avatar.
 *
 * @var \App\Application\Dto\AuthenticatedUser $authUser
 */
$view->layout('dashboard');

$avatar = $authUser->imageUrl !== null
    ? $authUser->imageUrl
    : $baseUrl . '/assets/admin/dist/img/user2-160x160.jpg';
?>
<div class="row">
    <div class="col-md-4">
        <?= $view->partial('components/card', [
            'title'  => 'Foto de perfil',
            'body'   => '<div class="usuario-perfil text-center">
                            <img src="' . e($avatar) . '" class="img-circle" alt="Avatar" style="max-width:160px">
                         </div>',
            'footer' => $view->partial('partials/forms/avatar', [
                'baseUrl'  => $baseUrl,
                'csrf'     => $csrf,
                'authUser' => $authUser,
            ]),
        ]) ?>
    </div>

    <div class="col-md-8">
        <?= $view->partial('components/card', [
            'title' => 'Datos de la cuenta',
            'body'  => '
                <div class="row">
                    <div class="col-md-6">
                        <label class="small text-muted mb-1">Usuario</label>
                        <p class="form-control-plaintext">' . e($authUser->username) . '</p>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted mb-1">Correo</label>
                        <p class="form-control-plaintext">' . e($authUser->email) . '</p>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted mb-1">Rol</label>
                        <p class="form-control-plaintext"><code>' . e($authUser->role) . '</code></p>
                    </div>
                </div>',
        ]) ?>
    </div>
</div>
