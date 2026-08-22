<?php
/**
 * Perfil del usuario en sesión.
 *
 * La foto se cambia sin recargar: se elige (o se arrastra), se ve la vista
 * previa, se confirma y la imagen se reemplaza en el sitio.
 *
 * @var \App\Application\Dto\AuthenticatedUser $authUser
 * @var \App\Presentation\View\ViewRenderer $view
 * @var \App\Infrastructure\Security\CsrfGuard $csrf
 * @var string $baseUrl
 * @var int $uploadMaxBytes
 */
$view->layout('dashboard');

$maxBytes = $uploadMaxBytes ?? 2097152;
$maxLabel = $maxBytes >= 1048576
    ? round($maxBytes / 1048576, 1) . ' MB'
    : round($maxBytes / 1024) . ' KB';

$avatarImg = $view->partial('components/avatar', [
    'avatarFile' => $authUser->avatar,
    'baseUrl' => $baseUrl,
    'size'    => '180',
    'classes' => 'avatar-img',
    'alt'     => 'Foto de ' . $authUser->username,
]);
?>
<div class="row">

    <!-- ============================ Avatar ============================ -->
    <div class="col-lg-4 mb-4">
        <div class="card card-avatar h-100">
            <div class="card-body text-center">

                <div class="avatar-wrap"
                     id="avatar-dropzone"
                     data-max-bytes="<?= e($maxBytes) ?>"
                     data-user-id="<?= e($authUser->id) ?>">

                    <div class="avatar-frame">
                        <?= $avatarImg ?>

                        <!-- Vista previa: sustituye a la foto mientras se confirma -->
                        <img src="" alt="Vista previa" class="avatar-img avatar-preview d-none" id="avatar-preview">

                        <!-- Velo con la barra de progreso -->
                        <div class="avatar-overlay" id="avatar-progress-overlay">
                            <div class="avatar-spinner"></div>
                            <span class="avatar-progress-text" id="avatar-progress-text">0%</span>
                        </div>

                        <!-- Zona visible al arrastrar un archivo encima -->
                        <div class="avatar-drop-hint">
                            <i class="fas fa-arrow-down"></i>
                            <span>Soltá la imagen</span>
                        </div>
                    </div>

                    <button type="button" class="avatar-camera" id="avatar-pick"
                            title="Cambiar foto" aria-label="Cambiar foto">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>

                <h5 class="mt-3 mb-0"><?= e($authUser->username) ?></h5>
                <p class="text-muted small mb-3"><?= e($authUser->email) ?></p>

                <p class="text-muted small avatar-hint" id="avatar-hint">
                    Arrastrá una imagen o tocá la cámara.<br>
                    JPG, PNG, GIF o WebP · máximo <?= e($maxLabel) ?>
                </p>

                <!-- Acciones cuando hay una imagen elegida sin confirmar -->
                <div class="d-none" id="avatar-confirm">
                    <p class="small mb-2" id="avatar-filename"></p>
                    <button type="button" class="btn btn-success btn-sm" id="avatar-save">
                        <i class="fas fa-check mr-1"></i> Guardar foto
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="avatar-cancel">
                        Cancelar
                    </button>
                </div>

                <!-- Quitar la foto actual: solo si tiene una -->
                <div id="avatar-actions" class="<?= $authUser->avatar === null ? 'd-none' : '' ?>">
                    <button type="button" class="btn btn-link btn-sm text-danger" id="avatar-remove">
                        <i class="fas fa-trash-alt mr-1"></i> Quitar foto
                    </button>
                </div>

                <!-- El formulario existe para llevar el token CSRF y el input.
                     El envío lo hace el JS con FormData. -->
                <form id="form-avatar" class="d-none" method="post" enctype="multipart/form-data"
                      action="<?= e($baseUrl) ?>/api/profile/image">
                    <?= $csrf->field() ?>
                    <input type="hidden" name="id" value="<?= e($authUser->id) ?>">
                    <input type="file" name="profile" id="avatar-file"
                           accept="image/png,image/jpeg,image/gif,image/webp">
                </form>
            </div>
        </div>
    </div>

    <!-- ========================= Datos cuenta ========================= -->
    <div class="col-lg-8">
        <?= $view->partial('components/card', [
            'title' => 'Datos de la cuenta',
            'body'  => '
                <dl class="row mb-0 profile-data">
                    <dt class="col-sm-3">Usuario</dt>
                    <dd class="col-sm-9">' . e($authUser->username) . '</dd>

                    <dt class="col-sm-3">Correo</dt>
                    <dd class="col-sm-9">' . e($authUser->email) . '</dd>

                    <dt class="col-sm-3">Rol</dt>
                    <dd class="col-sm-9"><span class="badge badge-dark">'
                        . e($authUser->role) . '</span></dd>
                </dl>',
        ]) ?>

        <?= $view->partial('components/card', [
            'title' => 'Seguridad',
            'body'  => '
                <p class="mb-2">¿Necesitás cambiar tu contraseña?</p>
                <a href="' . e($baseUrl) . '/password/forgot" class="btn btn-outline-dark btn-sm">
                    <i class="fas fa-key mr-1"></i> Cambiar contraseña
                </a>
                <p class="text-muted small mt-2 mb-0">
                    Te enviamos un enlace al correo para hacerlo de forma segura.
                </p>',
        ]) ?>
    </div>
</div>
