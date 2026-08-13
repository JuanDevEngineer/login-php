<?php
/**
 * Cabecera de tabla para DataTables. El cuerpo lo llena el JS.
 *
 * @var string   $id
 * @var string[] $columns
 */
?>
<table id="<?= e($id) ?>" class="table display responsive nowrap" style="width:100%">
    <thead>
        <tr class="bg-dark text-white text-center">
            <?php foreach ($columns as $column): ?>
                <th><?= e($column) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody class="text-center"></tbody>
</table>
