<?php
/**
 * Input con icono a la izquierda — el patrón que se repetía en todos los
 * formularios del proyecto.
 *
 * @var string      $name
 * @var string      $icon         clase de Font Awesome
 * @var string      $type
 * @var string      $placeholder
 * @var string      $value
 * @var bool        $disabled
 * @var bool        $required
 * @var string|null $id
 * @var string      $wrapper      clases del contenedor (columnas, etc.)
 */
$type        = $type        ?? 'text';
$value       = $value       ?? '';
$disabled    = $disabled    ?? false;
$required    = $required    ?? false;
$id          = $id          ?? $name;
$wrapper     = $wrapper     ?? '';
$placeholder = $placeholder ?? '';
$autocomplete = $autocomplete ?? null;
?>
<div class="input-group form-group <?= e($wrapper) ?>">
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="<?= e($icon) ?>"></i></span>
    </div>
    <input
        type="<?= e($type) ?>"
        name="<?= e($name) ?>"
        id="<?= e($id) ?>"
        class="form-control"
        placeholder="<?= e($placeholder) ?>"
        value="<?= e($value) ?>"
        <?= $autocomplete !== null ? 'autocomplete="' . e($autocomplete) . '"' : '' ?>
        <?= $disabled ? 'disabled' : '' ?>
        <?= $required ? 'required' : '' ?>>
</div>
