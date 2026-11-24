<?php

$rows = $rows ?? [];
$de = $de ?? date('Y-m-d', strtotime('-29 days'));
$ate = $ate ?? date('Y-m-d');
$summary = $summary ?? [];
$title = $title ?? 'Fluxo de caixa';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Entradas, saídas e saldo acumulado por dia.</p>
    </div>
    <a href="<?= e(url('financeiro.index')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="get" action="<?= e(url('financeiro.fluxo')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="r" value="financeiro.fluxo">
            <div class="col-md-3"><label for="de" class="form-label small fw-semibold">Início</label><input type="date" name="de" id="de" class="form-control" value="<?= e($de) ?>"></div>
            <div class="col-md-3"><label for="ate" class="form-label small fw-semibold">Fim</label><input type="date" name="ate" id="ate" class="form-control" value="<?= e($ate) ?>"></div>
            <div class="col-auto"><button type="submit" class="btn btn-outline-petcare"><i class="fa-solid fa-magnifying-glass me-1"></i>Filtrar</button></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-petcare h-100">
            <div class="card-body p-3">
                <span class="stat-card-label">Entradas no período</span>
                <div class="stat-card-value mt-2 text-success"><?= e(money($summary['entradas'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-petcare h-100">
            <div class="card-body p-3">
                <span class="stat-card-label">Saídas no período</span>
                <div class="stat-card-value mt-2 text-danger"><?= e(money($summary['saidas'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-petcare h-100">
            <div class="card-body p-3">
                <span class="stat-card-label">Saldo acumulado</span>
                <div class="stat-card-value mt-2 text-petcare"><?= e(money($summary['saldo'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted">Data</th>
                    <th class="small text-uppercase text-muted text-end">Entradas</th>
                    <th class="small text-uppercase text-muted text-end">Saídas</th>
                    <th class="small text-uppercase text-muted text-end">Saldo acumulado</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted"><i class="fa-regular fa-folder-open fa-2x d-block mb-2"></i>Nenhum lançamento no período.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e(date_br((string) ($row['dia'] ?? ''))) ?></td>
                            <td class="text-end text-success"><?= e(money($row['entradas'] ?? 0)) ?></td>
                            <td class="text-end text-danger"><?= e(money($row['saidas'] ?? 0)) ?></td>
                            <td class="text-end fw-semibold"><?= e(money($row['saldo'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
