<?php

$stats = $stats ?? [];
$chart = $chart ?? ['labels' => [], 'receitas' => [], 'despesas' => []];
$payments = $payments ?? [];
$shortcuts = $shortcuts ?? [];
$title = $title ?? 'Financeiro';

$chartsJson = json_encode(['financeiro' => $chart], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($chartsJson === false) {
    $chartsJson = '{}';
}
$cards = [
    ['label' => 'Receitas hoje', 'value' => money($stats['receitas_dia'] ?? 0), 'icon' => 'sack-dollar', 'color' => 'success'],
    ['label' => 'Receitas do mês', 'value' => money($stats['receitas_mes'] ?? 0), 'icon' => 'arrows-up-to-line', 'color' => 'success'],
    ['label' => 'Despesas do mês', 'value' => money($stats['despesas_mes'] ?? 0), 'icon' => 'arrows-down-to-line', 'color' => 'danger'],
    ['label' => 'Saldo do mês', 'value' => money($stats['saldo'] ?? 0), 'icon' => 'scale-balanced', 'color' => 'primary'],
    ['label' => 'Contas a receber', 'value' => (int) ($stats['contas_receber'] ?? 0), 'icon' => 'hand-holding-dollar', 'color' => 'info'],
    ['label' => 'Contas a pagar', 'value' => (int) ($stats['contas_pagar'] ?? 0), 'icon' => 'file-invoice-dollar', 'color' => 'warning'],
    ['label' => 'Contas vencidas', 'value' => (int) ($stats['vencidas'] ?? 0), 'icon' => 'triangle-exclamation', 'color' => 'danger'],
];
?>
<div id="petCareChartsData" data-charts="<?= e($chartsJson) ?>" hidden></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    window.PetCareCharts = JSON.parse(document.getElementById('petCareChartsData').dataset.charts);
</script>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Acompanhe entradas, saídas e contas em aberto da clínica.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(url('financeiro.receber')) ?>" class="btn btn-outline-success"><i class="fa-solid fa-arrow-down-to-line me-1"></i>Receber</a>
        <a href="<?= e(url('financeiro.pagar')) ?>" class="btn btn-outline-danger"><i class="fa-solid fa-arrow-up-to-line me-1"></i>Pagar</a>
        <a href="<?= e(url('financeiro.fluxo')) ?>" class="btn btn-petcare"><i class="fa-solid fa-chart-line me-2"></i>Fluxo de caixa</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($cards as $card): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card stat-card border-0 shadow-petcare h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span class="stat-card-label"><?= e($card['label']) ?></span>
                        <span class="stat-card-icon text-<?= e($card['color']) ?>"><i class="fa-solid fa-<?= e($card['icon']) ?>"></i></span>
                    </div>
                    <div class="stat-card-value mt-2"><?= e($card['value']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-chart-column me-2"></i>Receitas x despesas</h2>
                <span class="badge bg-petcare-soft">Últimos 6 meses</span>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="chart-wrap"><canvas id="chartFinanceiro" aria-label="Gráfico de receitas e despesas dos últimos seis meses" role="img"></canvas></div>
                <?php if (($chart['labels'] ?? []) === []): ?>
                    <p class="chart-empty text-muted small text-center mb-0">Nenhum lançamento financeiro para exibir.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-bolt me-2"></i>Atalhos</h2></div>
            <div class="card-body p-3 p-md-4">
                <?php if ($shortcuts === []): ?>
                    <div class="empty-state"><i class="fa-regular fa-square-plus"></i><p class="mb-0">Nenhum atalho disponível.</p></div>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($shortcuts as $shortcut): ?>
                            <div class="col-6">
                                <a href="<?= e(url($shortcut['route'])) ?>" class="shortcut-card text-decoration-none">
                                    <span class="shortcut-icon text-<?= e($shortcut['color'] ?? 'primary') ?>"><i class="fa-solid fa-<?= e($shortcut['icon'] ?? 'circle') ?>"></i></span>
                                    <span class="shortcut-label"><?= e($shortcut['label'] ?? 'Acessar') ?></span>
                                    <i class="fa-solid fa-chevron-right shortcut-arrow"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-clock-rotate-left me-2"></i>Últimos pagamentos</h2>
        <a href="<?= e(url('financeiro.fluxo')) ?>" class="small text-decoration-none">Ver fluxo completo</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted">Data</th>
                    <th class="small text-uppercase text-muted">Tipo</th>
                    <th class="small text-uppercase text-muted">Participante</th>
                    <th class="small text-uppercase text-muted">Forma de pagamento</th>
                    <th class="small text-uppercase text-muted text-end">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments === []): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fa-regular fa-folder-open fa-2x d-block mb-2"></i>Nenhum pagamento registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= e(date_br((string) ($payment['data_pagamento'] ?? ''), true)) ?></td>
                            <td><span class="badge bg-<?= ($payment['tipo'] ?? '') === 'Recebimento' ? 'success' : 'danger' ?> rounded-pill"><?= e($payment['tipo'] ?? '—') ?></span></td>
                            <td><?= e($payment['participante'] ?? '—') ?></td>
                            <td><?= e($payment['forma_pagamento'] ?? '—') ?></td>
                            <td class="text-end fw-semibold"><?= e(money($payment['valor'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
