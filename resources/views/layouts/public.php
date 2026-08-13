<?php
/**
 * Layout de las páginas públicas (login, registro, recuperación).
 * Abre y cierra todo su propio HTML: ninguna etiqueta queda a medias entre
 * archivos, que era el problema del viejo header.php / footer.php.
 *
 * @var string $content  HTML de la página
 * @var string $baseUrl
 * @var string $title
 */
$title = $title ?? 'App Login';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrf->token()) ?>">
    <title><?= e($title) ?></title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
          integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css"
          integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e($baseUrl) ?>/assets/toastr/toastr.min.css">
    <link rel="stylesheet" href="<?= e($baseUrl) ?>/public/css/main.css">
</head>
<body data-base-url="<?= e($baseUrl) ?>">

<?= $view->partial('partials/public/navbar', ['baseUrl' => $baseUrl, 'authUser' => $authUser ?? null]) ?>

<main>
    <?= $content ?>
</main>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"
        integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"
        integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="<?= e($baseUrl) ?>/assets/toastr/toastr.min.js"></script>
<script src="<?= e($baseUrl) ?>/public/js/app.js"></script>
<?= $sections['scripts'] ?? '' ?>
</body>
</html>
