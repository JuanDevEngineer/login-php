<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <!--Fontawesome CDN-->
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
    <link type="text/css" href="<?php echo e(BASE_URL) ?>/assets/toastr/toastr.min.css" rel="stylesheet"/>
    <link type="text/css" href="<?php echo e(BASE_URL) ?>/public/css/main.css" rel="stylesheet"/>
    <title><?php echo e(paginaActual()) ?></title>
</head>

<body data-base-url="<?php echo e(BASE_URL); ?>">

    <header class="container">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <a class="navbar-brand " href="#"></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto">
                </ul>

                <?php if(!isset($_SESSION['email'])): ?>

                    <ul class="navbar-nav">
                        <li class="nav-item mr-2">
                            <a class="nav-link active acceso" href="<?php echo e(BASE_URL)?>/App/acceso">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active acceso" href="<?php echo e(BASE_URL)?>/App/registro">
                                <i class="fas fa-users"></i> Registro
                            </a>
                        </li>
                    </ul>

                <?php else: ?>

                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link active acceso" href="<?php echo e(BASE_URL)?>/App/acceso">
                                <i class="fas fa-sign-in-alt"></i> Carrito
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active acceso" href="<?php echo e(BASE_URL)?>/App/registro">
                                <i class="fas fa-users"></i> Productos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active acceso" href="<?php echo e(BASE_URL); ?>/App/logout">
                                <i class="fas fa-power-off"></i> Logout
                            </a>
                        </li>
                    </ul>

                <?php endif; ?>

            </div>
        </nav>
    </header>