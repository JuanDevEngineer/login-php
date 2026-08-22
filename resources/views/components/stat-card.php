<?php
/**
 * Card de métrica del panel.
 *
 * Accesibilidad (WCAG 2.2 AA):
 *  - Cada card es un <article> con encabezado real, así un lector de pantalla
 *    puede navegarlas por landmarks en vez de oír una sopa de números.
 *  - La cifra lleva aria-label con la lectura completa ("128 usuarios
 *    registrados"); el número suelto no dice nada fuera de contexto.
 *  - El icono es decorativo: aria-hidden y sin texto alternativo.
 *  - El acento NO es solo color: cada card lleva icono y etiqueta propios,
 *    porque el color por sí solo no puede transmitir información (1.4.1).
 *
 * @var string      $label   qué mide
 * @var int|string  $value   la cifra
 * @var string      $icon    clase Font Awesome, decorativa
 * @var string      $accent  'ink' | 'amber' | 'teal'
 * @var string|null $hint    texto de apoyo bajo la cifra
 * @var string|null $srValue lectura completa para lectores de pantalla
 */
$hint    = $hint    ?? null;
$accent  = $accent  ?? 'ink';
$srValue = $srValue ?? ($value . ' ' . $label);
$cardId  = 'stat-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($label));
?>
<article class="stat-card stat-card--<?= e($accent) ?>" aria-labelledby="<?= e($cardId) ?>-label">
    <div class="stat-card__head">
        <h3 class="stat-card__label" id="<?= e($cardId) ?>-label"><?= e($label) ?></h3>
        <span class="stat-card__icon" aria-hidden="true">
            <i class="<?= e($icon) ?>"></i>
        </span>
    </div>

    <p class="stat-card__value">
        <span aria-hidden="true"><?= e($value) ?></span>
        <span class="visually-hidden"><?= e($srValue) ?></span>
    </p>

    <?php if ($hint !== null): ?>
        <p class="stat-card__hint"><?= e($hint) ?></p>
    <?php endif; ?>
</article>
