<?php
/** @var string $title @var string[] $breadcrumb @var string $baseUrl */
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?= e($title) ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= e($baseUrl) ?>/dashboard">Inicio</a>
                    </li>
                    <?php foreach ($breadcrumb as $i => $crumb): ?>
                        <li class="breadcrumb-item <?= $i === count($breadcrumb) - 1 ? 'active' : '' ?>">
                            <?= e($crumb) ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</div>
