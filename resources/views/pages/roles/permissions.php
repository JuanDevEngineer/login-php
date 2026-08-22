<?php
/**
 * Matriz de permisos por rol.
 *
 * Accesibilidad: es una <table> real con <caption>, cabeceras de fila y de
 * columna asociadas por scope, y cada checkbox con su propio nombre accesible.
 * Una rejilla de divs sería imposible de recorrer con lector de pantalla.
 *
 * @var array{groups: array<string, list<array{code:string,label:string,description:string}>>,
 *            roles: list<array{id:int,name:string,system:bool,editable:bool,granted:list<string>,total:int}>} $matrix
 * @var \App\Presentation\View\ViewRenderer $view
 * @var \App\Infrastructure\Security\CsrfGuard $csrf
 * @var string $baseUrl
 */
$view->layout('dashboard');

$roles  = $matrix['roles'];
$groups = $matrix['groups'];
?>

<div class="alert alert-secondary" role="note">
    <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
    Los permisos se asignan al <strong>rol</strong>, no a la persona. Quien tenga
    ese rol hereda lo que marques acá, y el cambio surte efecto de inmediato
    aunque tenga la sesión abierta.
</div>

<form id="form-permisos" method="post" action="<?= e($baseUrl) ?>/api/roles/permissions">
    <?= $csrf->field() ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Matriz de permisos</h3>
            <div class="card-tools">
                <label class="mb-0 mr-2 small" for="rol-activo">Rol a editar</label>
                <select id="rol-activo" class="form-control form-control-sm d-inline-block w-auto">
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= e($rol['id']) ?>"
                                data-editable="<?= $rol['editable'] ? '1' : '0' ?>"
                                data-granted="<?= e(json_encode($rol['granted'])) ?>">
                            <?= e($rol['name']) ?><?= $rol['editable'] ? '' : ' (acceso total)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="card-body">
            <input type="hidden" name="rol_id" id="rol_id" value="<?= e($roles[0]['id'] ?? '') ?>">

            <p id="rol-bloqueado" class="alert alert-warning d-none" role="status">
                <i class="fas fa-lock mr-1" aria-hidden="true"></i>
                Este rol tiene <strong>acceso total por definición</strong>. Sus permisos
                no se administran acá: es la red de seguridad que garantiza que siempre
                quede alguien capaz de entrar a corregir la configuración.
            </p>

            <table class="table table-hover permissions-table">
                <caption class="sr-only-caption">
                    Permisos disponibles y si el rol seleccionado los tiene concedidos
                </caption>
                <thead>
                    <tr>
                        <th scope="col" style="width:60px">Activo</th>
                        <th scope="col">Permiso</th>
                        <th scope="col">Qué habilita</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $grupo => $permisos): ?>
                        <tr class="permissions-group">
                            <th scope="colgroup" colspan="3"><?= e($grupo) ?></th>
                        </tr>
                        <?php foreach ($permisos as $permiso): ?>
                            <?php $inputId = 'perm-' . str_replace('.', '-', $permiso['code']); ?>
                            <tr>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox"
                                               class="custom-control-input permission-check"
                                               id="<?= e($inputId) ?>"
                                               name="permisos[]"
                                               value="<?= e($permiso['code']) ?>">
                                        <label class="custom-control-label" for="<?= e($inputId) ?>">
                                            <span class="visually-hidden">
                                                Conceder <?= e($permiso['label']) ?>
                                            </span>
                                        </label>
                                    </div>
                                </td>
                                <th scope="row" class="font-weight-normal">
                                    <?= e($permiso['label']) ?><br>
                                    <code class="small text-muted"><?= e($permiso['code']) ?></code>
                                </th>
                                <td class="text-muted small"><?= e($permiso['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="permisos-resumen" role="status" aria-live="polite"></span>
            <button type="submit" class="btn btn-dark" id="permisos-guardar">
                <i class="fas fa-save mr-1" aria-hidden="true"></i> Guardar permisos
            </button>
        </div>
    </div>
</form>
