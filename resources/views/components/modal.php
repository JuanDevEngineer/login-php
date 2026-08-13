<?php
/**
 * Modal de Bootstrap.
 *
 * @var string $id
 * @var string $title
 * @var string $body   HTML ya renderizado
 * @var string $size   'modal-lg' | 'modal-sm' | ''
 */
$size = $size ?? 'modal-lg';
?>
<div class="modal fade" id="<?= e($id) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog <?= e($size) ?>">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h4 class="modal-title"><?= e($title) ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"><?= $body ?></div>
        </div>
    </div>
</div>
