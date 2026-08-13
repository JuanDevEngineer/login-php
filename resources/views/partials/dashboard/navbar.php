<?php
/** @var string $baseUrl @var \App\Application\Dto\AuthenticatedUser|null $authUser */
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Contraer menú">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= e($baseUrl) ?>/dashboard" class="nav-link">Inicio</a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" role="button">
                <i class="fas fa-user-circle"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <span class="dropdown-item dropdown-header">
                    <?= e($authUser !== null ? $authUser->username : '') ?>
                </span>
                <div class="dropdown-divider"></div>
                <a href="<?= e($baseUrl) ?>/profile" class="dropdown-item">
                    <i class="fas fa-id-card mr-2"></i> Mi perfil
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= e($baseUrl) ?>/logout" class="dropdown-item">
                    <i class="fas fa-power-off mr-2"></i> Cerrar sesión
                </a>
            </div>
        </li>
    </ul>
</nav>
