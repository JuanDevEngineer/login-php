<?php
/**
 * Layout del panel AdminLTE.
 *
 * @var string $content
 * @var string $baseUrl
 * @var string $title
 * @var \App\Application\Dto\AuthenticatedUser|null $authUser
 * @var string[] $breadcrumb
 */
$title      = $title ?? 'Panel';
$breadcrumb = $breadcrumb ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf->token()) ?>">
    <title><?= e($title) ?> · Panel</title>

    <link rel="stylesheet" href="<?= e($baseUrl) ?>/assets/admin/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= e($baseUrl) ?>/assets/admin/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="<?= e($baseUrl) ?>/assets/admin/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= e($baseUrl) ?>/public/css/dashboard.css">
    <link rel="stylesheet" href="<?= e($baseUrl) ?>/public/css/usuarios.css">
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="//cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <link rel="stylesheet" href="<?= e($baseUrl) ?>/assets/toastr/toastr.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
<body class="hold-transition sidebar-mini layout-fixed" data-base-url="<?= e($baseUrl) ?>">
<div class="wrapper">

    <?= $view->partial('partials/dashboard/navbar', ['baseUrl' => $baseUrl, 'authUser' => $authUser]) ?>

    <?= $view->partial('partials/dashboard/sidebar', ['baseUrl' => $baseUrl, 'authUser' => $authUser]) ?>

    <div class="content-wrapper">
        <?= $view->partial('partials/dashboard/content-header', [
            'title'      => $title,
            'breadcrumb' => $breadcrumb,
            'baseUrl'    => $baseUrl,
        ]) ?>

        <section class="content">
            <div class="container-fluid">
                <?= $content ?>
            </div>
        </section>
    </div>

    <?= $view->partial('partials/dashboard/footer') ?>
</div>

<script src="<?= e($baseUrl) ?>/assets/admin/plugins/jquery/jquery.min.js"></script>
<script src="<?= e($baseUrl) ?>/assets/admin/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= e($baseUrl) ?>/assets/admin/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="<?= e($baseUrl) ?>/assets/admin/dist/js/adminlte.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
<script src="//cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="<?= e($baseUrl) ?>/assets/toastr/toastr.min.js"></script>
<script src="<?= e($baseUrl) ?>/public/js/usuarios.js"></script>
<script src="<?= e($baseUrl) ?>/public/js/roles.js"></script>
<?= $sections['scripts'] ?? '' ?>
</body>
</html>
