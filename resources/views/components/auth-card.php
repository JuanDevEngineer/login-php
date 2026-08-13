<?php
/**
 * Tarjeta centrada de las pantallas públicas (login, registro, recuperación).
 *
 * @var string      $title
 * @var string      $body    HTML del formulario
 * @var string|null $footer
 */
$footer = $footer ?? null;
?>
<div class="container">
    <div class="d-flex justify-content-center">
        <div class="card p-card">
            <div class="card-header">
                <h3><?= e($title) ?></h3>
                <div class="d-flex justify-content-end social_icon">
                    <span><i class="fas fa-user"></i></span>
                </div>
            </div>
            <div class="card-body"><?= $body ?></div>
            <?php if ($footer !== null): ?>
                <div class="card-footer"><?= $footer ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
