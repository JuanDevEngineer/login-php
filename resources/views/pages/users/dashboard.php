<?php
/**
 * Panel de inicio.
 *
 * @var \App\Presentation\View\ViewRenderer $view
 * @var \App\Application\Dto\AuthenticatedUser $authUser
 * @var \App\Application\Dto\DashboardMetrics $metrics
 * @var string $baseUrl
 */
$view->layout('dashboard');

$inactiveHint = $metrics->totalUsers > 0
    ? $metrics->inactiveRatio() . '% del total · ' . $metrics->activeUsers() . ' activos'
    : 'Todavía no hay usuarios';
?>

<section aria-labelledby="metricas-titulo" class="mb-2">
    <h2 id="metricas-titulo" class="visually-hidden">Métricas del sistema</h2>

    <div class="stats-grid">
        <?= $view->partial('components/stat-card', [
            'label'   => 'Usuarios registrados',
            'value'   => $metrics->totalUsers,
            'icon'    => 'fas fa-users',
            'accent'  => 'ink',
            'hint'    => 'Total de cuentas creadas',
            'srValue' => $metrics->totalUsers . ' usuarios registrados en total',
        ]) ?>

        <?= $view->partial('components/stat-card', [
            'label'   => 'Usuarios inactivos',
            'value'   => $metrics->inactiveUsers,
            'icon'    => 'fas fa-user-slash',
            'accent'  => 'amber',
            'hint'    => $inactiveHint,
            'srValue' => $metrics->inactiveUsers . ' usuarios inactivos, '
                         . $metrics->activeUsers() . ' activos',
        ]) ?>

        <?= $view->partial('components/stat-card', [
            'label'   => 'Ingresos del mes',
            'value'   => $metrics->loginsThisMonth,
            'icon'    => 'fas fa-right-to-bracket',
            'accent'  => 'teal',
            'hint'    => 'Inicios de sesión en ' . $metrics->monthLabel(),
            'srValue' => $metrics->loginsThisMonth . ' inicios de sesión durante '
                         . $metrics->monthLabel(),
        ]) ?>
    </div>
</section>

<div class="row">
    <div class="col-12">
        <?= $view->partial('components/card', [
            'title' => 'Bienvenido, ' . $authUser->username,
            'body'  => '<p class="mb-0">Sesión iniciada como <strong>'
                       . e($authUser->email) . '</strong> con rol <code>'
                       . e($authUser->role) . '</code>.</p>',
        ]) ?>
    </div>
</div>
