<?php
/**
 * Card de AdminLTE.
 *
 * @var string      $title
 * @var string      $body     HTML ya renderizado del cuerpo
 * @var string|null $footer
 * @var bool        $tools    mostrar botón de contraer
 * @var string|null $actions  HTML de botones a la derecha del encabezado
 * @var string      $classes
 */
$title   = $title   ?? '';
$footer  = $footer  ?? null;
$tools   = $tools   ?? false;
$actions = $actions ?? null;
$classes = $classes ?? '';
?>
<div class="card <?= e($classes) ?>">
    <?php if ($title !== '' || $tools || $actions !== null): ?>
        <div class="card-header">
            <h3 class="card-title"><?= e($title) ?></h3>
            <div class="card-tools">
                <?= $actions ?? '' ?>
                <?php if ($tools): ?>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Contraer">
                        <i class="fas fa-minus"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card-body"><?= $body ?></div>

    <?php if ($footer !== null): ?>
        <div class="card-footer"><?= $footer ?></div>
    <?php endif; ?>
</div>
