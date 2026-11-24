<?php

/** @var string $title */
/** @var array $columns */
/** @var array $rows */
/** @var string $route */
/** @var string|null $createRoute */
/** @var string $search */
/** @var array|null $paginator */
/** @var array $actions */

$route = $route ?? '';
$title = $title ?? 'Registros';
$rows = $rows ?? [];
$columns = $columns ?? [];
$search = $search ?? '';
$createRoute = $createRoute ?? null;
$paginator = $paginator ?? null;
$actions = $actions ?? [
    ['label' => 'Visualizar', 'route' => $route . '.show', 'icon' => 'eye', 'class' => 'btn-outline-primary'],
    ['label' => 'Editar', 'route' => $route . '.edit', 'icon' => 'pen', 'class' => 'btn-outline-secondary'],
    ['label' => 'Excluir', 'route' => $route . '.destroy', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Deseja realmente excluir este registro?'],
];
$emptyMessage = $emptyMessage ?? 'Nenhum registro encontrado.';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Gerencie os registros do sistema PetCare.</p>
    </div>
    <?php if ($createRoute): ?>
        <a href="<?= e(url($createRoute)) ?>" class="btn btn-petcare shadow-sm">
            <i class="fa-solid fa-plus me-2"></i>Novo registro
        </a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="get" action="<?= e(url($route . '.index')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="r" value="<?= e($route . '.index') ?>">
            <div class="col-md-6 col-lg-5">
                <label for="q" class="form-label small fw-semibold">Pesquisar</label>
                <input type="search" name="q" id="q" class="form-control" value="<?= e($search) ?>" placeholder="Digite o termo da busca">
            </div>
            <?php if (!empty($extraFilters)): ?>
                <?= $extraFilters ?>
            <?php endif; ?>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-petcare"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</button>
            </div>
            <?php if ($search !== ''): ?>
                <div class="col-auto">
                    <a href="<?= e(url($route . '.index')) ?>" class="btn btn-link text-decoration-none">Limpar</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <?php foreach ($columns as $label => $key): ?>
                        <th class="small text-uppercase text-muted fw-bold"><?= e($label) ?></th>
                    <?php endforeach; ?>
                    <?php if ($actions !== []): ?><th class="text-end small text-uppercase text-muted fw-bold">Ações</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= count($columns) + ($actions !== [] ? 1 : 0) ?>" class="text-center py-5 text-muted"><i class="fa-regular fa-folder-open fa-2x d-block mb-2"></i><?= e($emptyMessage) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($columns as $label => $key): ?>
                                <td>
                                    <?php
                                    if (is_callable($key)) {
                                        echo $key($row);
                                    } elseif ((string) $key === 'status') {
                                        echo status_badge($row['status'] ?? null);
                                    } else {
                                        $value = $row[$key] ?? '—';
                                        if ($value === null || $value === '') {
                                            $value = '—';
                                        }
                                        echo e($value);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <?php if ($actions !== []): ?>
                                <td>
                                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                                        <?php foreach ($actions as $action): ?>
                                            <?php
                                            $actionRoute = (string) ($action['route'] ?? '');
                                            $actionParams = str_contains($actionRoute, ':id') ? [] : ['id' => (int) ($row['id'] ?? 0)];
                                            echo render_action(str_replace(':id', (string) ($row['id'] ?? ''), $actionRoute), $actionParams, $action);
                                            ?>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($paginator)): ?>
        <div class="card-footer bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">Exibindo <?= (int) $paginator['total'] ?> registro(s) — página <?= (int) $paginator['page'] ?> de <?= (int) $paginator['pages'] ?></small>
            <?= pagination($paginator, $route . '.index', array_filter(['q' => $search])) ?>
        </div>
    <?php endif; ?>
</div>
