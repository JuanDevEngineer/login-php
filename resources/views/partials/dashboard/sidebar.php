<?php
/**
 * Menú lateral. Los ítems se declaran como datos y se pintan en un bucle: para
 * agregar una sección se toca este array, no el HTML.
 *
 * @var string $baseUrl
 * @var \App\Application\Dto\AuthenticatedUser|null $authUser
 */
$isAdmin = $authUser !== null && $authUser->isAdmin();

$menu = [
    [
        'label' => 'Inicio',
        'icon'  => 'fas fa-tachometer-alt',
        'url'   => $baseUrl . '/dashboard',
    ],
    [
        'label' => 'Mi perfil',
        'icon'  => 'fas fa-id-card',
        'url'   => $baseUrl . '/profile',
    ],
];

if ($isAdmin) {
    $menu[] = [
        'label'    => 'Administración',
        'icon'     => 'fas fa-users-cog',
        'children' => [
            ['label' => 'Gestor de usuarios', 'url' => $baseUrl . '/users'],
            ['label' => 'Gestor de roles',    'url' => $baseUrl . '/roles'],
        ],
    ];
}

?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= e($baseUrl) ?>/dashboard" class="brand-link">
        <img src="<?= e($baseUrl) ?>/assets/admin/dist/img/AdminLTELogo.png" alt=""
             class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">LOGIN</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <?= $view->partial('components/avatar', [
                    'avatarFile' => $authUser !== null ? $authUser->avatar : null,
                    'baseUrl' => $baseUrl,
                    'size'    => '34',
                    'classes' => 'img-circle elevation-2',
                ]) ?>
            </div>
            <div class="info">
                <a href="<?= e($baseUrl) ?>/profile" class="d-block">
                    <?= e($authUser !== null ? $authUser->username : '') ?>
                </a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <?php foreach ($menu as $item): ?>
                    <?php if (empty($item['children'])): ?>
                        <li class="nav-item">
                            <a href="<?= e($item['url']) ?>" class="nav-link">
                                <i class="nav-icon <?= e($item['icon']) ?>"></i>
                                <p><?= e($item['label']) ?></p>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="nav-icon <?= e($item['icon']) ?>"></i>
                                <p>
                                    <?= e($item['label']) ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li class="nav-item">
                                        <a href="<?= e($child['url']) ?>" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p><?= e($child['label']) ?></p>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</aside>
