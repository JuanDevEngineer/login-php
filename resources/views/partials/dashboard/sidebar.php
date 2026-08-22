<?php

use App\Domain\ValueObject\Permission;

/**
 * Menú lateral, filtrado por permisos.
 *
 * Cada entrada declara qué permiso exige y se oculta si el usuario no lo tiene.
 * Antes se preguntaba por `isAdmin()`, lo que dejaba el menú fuera del sistema
 * de permisos: un rol con `usuarios.ver` habría podido entrar por URL directa
 * pero no habría visto el enlace.
 *
 * Ocultar no es proteger: quien tiene el enlace oculto tampoco pasa el control
 * del router. Esto es solo para no ofrecer lo que va a devolver un 403.
 *
 * @var string $baseUrl
 * @var \App\Application\Dto\AuthenticatedUser|null $authUser
 * @var \App\Domain\ValueObject\PermissionSet $userPermissions
 */
$permissions = $userPermissions ?? \App\Domain\ValueObject\PermissionSet::empty();

$can = static fn (Permission $p): bool => $permissions->has($p);

$menu = [
    [
        'label'      => 'Inicio',
        'icon'       => 'fas fa-tachometer-alt',
        'url'        => $baseUrl . '/dashboard',
        'permission' => Permission::PanelVer,
    ],
    [
        'label'      => 'Mi perfil',
        'icon'       => 'fas fa-id-card',
        'url'        => $baseUrl . '/profile',
        'permission' => null, // el propio perfil no exige permiso
    ],
    [
        'label'    => 'Administración',
        'icon'     => 'fas fa-users-cog',
        'children' => [
            [
                'label'      => 'Gestor de usuarios',
                'url'        => $baseUrl . '/users',
                'permission' => Permission::UsuariosVer,
            ],
            [
                'label'      => 'Gestor de roles',
                'url'        => $baseUrl . '/roles',
                'permission' => Permission::RolesVer,
            ],
            [
                'label'      => 'Permisos por rol',
                'url'        => $baseUrl . '/roles/permisos',
                'permission' => Permission::PermisosGestionar,
            ],
        ],
    ],
];

// Filtra hijos y descarta las secciones que se quedan sin ninguno.
$visible = [];
foreach ($menu as $item) {
    if (isset($item['children'])) {
        $children = array_values(array_filter(
            $item['children'],
            static fn (array $child): bool => $child['permission'] === null || $can($child['permission'])
        ));

        if ($children !== []) {
            $item['children'] = $children;
            $visible[] = $item;
        }
        continue;
    }

    if ($item['permission'] === null || $can($item['permission'])) {
        $visible[] = $item;
    }
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
                <?php foreach ($visible as $item): ?>
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
