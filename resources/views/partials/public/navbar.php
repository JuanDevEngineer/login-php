<?php
/** @var string $baseUrl @var \App\Application\Dto\AuthenticatedUser|null $authUser */
?>
<header class="container">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="<?= e($baseUrl) ?>/">APP-LOGIN</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Abrir navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ml-auto">
                <?php if ($authUser === null): ?>
                    <li class="nav-item mr-2">
                        <a class="nav-link acceso" href="<?= e($baseUrl) ?>/login">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link acceso" href="<?= e($baseUrl) ?>/register">
                            <i class="fas fa-users"></i> Registro
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item mr-2">
                        <a class="nav-link acceso" href="<?= e($baseUrl) ?>/dashboard">
                            <i class="fas fa-columns"></i> Panel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link acceso" href="<?= e($baseUrl) ?>/logout">
                            <i class="fas fa-power-off"></i> Salir
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>
