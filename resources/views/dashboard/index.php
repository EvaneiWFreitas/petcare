<?php
$title = $title ?? 'Painel';
$stats = $stats ?? [];
$charts = $charts ?? [];
$activities = $activities ?? [];
$upcoming = $upcoming ?? [];
$shortcuts = $shortcuts ?? [];

$statCards = [
    ['label' => 'Clientes', 'value' => (int) ($stats['clientes'] ?? 0), 'icon' => 'users', 'color' => 'primary', 'hint' => 'cadastrados no sistema'],
    ['label' => 'Animais', 'value' => (int) ($stats['animais'] ?? 0), 'icon' => 'paw', 'color' => 'success', 'hint' => 'pacientes acompanhados'],
    ['label' => 'Agendados', 'value' => (int) ($stats['agendados'] ?? 0), 'icon' => 'calendar-check', 'color' => 'info', 'hint' => 'agendamentos ativos'],
    ['label' => 'Realizados', 'value' => (int) ($stats['realizados'] ?? 0), 'icon' => 'circle-check', 'color' => 'success', 'hint' => 'atendimentos concluídos'],
    ['label' => 'Cancelados', 'value' => (int) ($stats['cancelados'] ?? 0), 'icon' => 'circle-xmark', 'color' => 'danger', 'hint' => 'agendamentos cancelados'],
    ['label' => 'Atendimentos hoje', 'value' => (int) ($stats['atendimentos_hoje'] ?? 0), 'icon' => 'stethoscope', 'color' => 'primary', 'hint' => 'no dia de hoje'],
    ['label' => 'Vacinas próximas', 'value' => (int) ($stats['vacinas_proximas'] ?? 0), 'icon' => 'syringe', 'color' => 'info', 'hint' => 'próximas doses'],
    ['label' => 'Vacinas atrasadas', 'value' => (int) ($stats['vacinas_atrasadas'] ?? 0), 'icon' => 'triangle-exclamation', 'color' => 'warning', 'hint' => 'doses pendentes'],
    ['label' => 'Aniversariantes', 'value' => (int) ($stats['aniversariantes'] ?? 0), 'icon' => 'cake-candles', 'color' => 'warning', 'hint' => 'neste mês'],
    ['label' => 'Estoque baixo', 'value' => (int) ($stats['estoque_baixo'] ?? 0), 'icon' => 'boxes-stacked', 'color' => 'danger', 'hint' => 'itens para repor'],
    ['label' => 'Produtos vencendo', 'value' => (int) ($stats['produtos_vencendo'] ?? 0), 'icon' => 'hourglass', 'color' => 'warning', 'hint' => 'próximos ao vencimento'],
    ['label' => 'Receitas do dia', 'value' => money($stats['receitas_dia'] ?? 0), 'icon' => 'sack-dollar', 'color' => 'success', 'hint' => 'entradas de hoje'],
    ['label' => 'Receitas do mês', 'value' => money($stats['receitas_mes'] ?? 0), 'icon' => 'arrows-up-to-line', 'color' => 'success', 'hint' => 'entradas do mês'],
    ['label' => 'Despesas do mês', 'value' => money($stats['despesas_mes'] ?? 0), 'icon' => 'arrows-down-to-line', 'color' => 'danger', 'hint' => 'saídas do mês'],
    ['label' => 'Saldo', 'value' => money($stats['saldo'] ?? 0), 'icon' => 'scale-balanced', 'color' => 'primary', 'hint' => 'resultado financeiro'],
];

$allowedColors = ['primary', 'success', 'info', 'warning', 'danger'];
$consultasData = $charts['consultas_mes'] ?? ['labels' => [], 'data' => []];
$financeiroData = $charts['financeiro'] ?? ['labels' => [], 'receitas' => [], 'despesas' => []];
$clientesData = $charts['clientes_mes'] ?? ['labels' => [], 'data' => []];

$chartsJson = json_encode($charts, JSON_UNESCAPED_UNICODE);
if ($chartsJson === false) {
    $chartsJson = '{}';
}
?>
<div id="petCareChartsData" data-charts="<?= e($chartsJson) ?>" hidden></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    window.PetCareCharts = JSON.parse(document.getElementById('petCareChartsData').dataset.charts);
</script>

<div class="dashboard-hero mb-4">
    <div>
        <h1 class="h3 fw-bold text-petcare mb-1">Olá, <?= e(explode(' ', trim((string) (user()['nome'] ?? 'Usuário')))[0] ?: 'Usuário') ?>!</h1>
        <p class="text-muted mb-0">Acompanhe o que está acontecendo na sua clínica hoje, <?= e(date_br(date('Y-m-d'))) ?>.</p>
    </div>
    <a href="<?= e(url('agenda.index')) ?>" class="btn btn-petcare shadow-petcare mt-3 mt-md-0">
        <i class="fa-solid fa-calendar-plus me-2"></i>Ver agenda
    </a>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($statCards as $card): ?>
        <?php $color = in_array($card['color'], $allowedColors, true) ? $card['color'] : 'primary'; ?>
        <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
            <div class="card stat-card border-0 shadow-petcare h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span class="stat-card-label"><?= e($card['label']) ?></span>
                        <span class="stat-card-icon text-<?= e($color) ?>"><i class="fa-solid fa-<?= e($card['icon']) ?>"></i></span>
                    </div>
                    <div class="stat-card-value mt-2"><?= e($card['value']) ?></div>
                    <div class="stat-card-hint"><?= e($card['hint']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-calendar-days me-2"></i>Consultas por mês</h2>
                <span class="badge bg-petcare-soft">Últimos meses</span>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="chart-wrap">
                    <canvas id="chartConsultas" aria-label="Gráfico de consultas por mês" role="img"></canvas>
                </div>
                <?php if (empty($consultasData['labels'])): ?>
                    <p class="chart-empty text-muted small text-center mb-0">Nenhuma consulta registrada para exibir.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-chart-column me-2"></i>Financeiro</h2>
                <span class="badge bg-petcare-soft">Receitas x despesas</span>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="chart-wrap">
                    <canvas id="chartFinanceiro" aria-label="Gráfico financeiro de receitas e despesas" role="img"></canvas>
                </div>
                <?php if (empty($financeiroData['labels'])): ?>
                    <p class="chart-empty text-muted small text-center mb-0">Nenhum lançamento financeiro para exibir.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3">
                <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-user-plus me-2"></i>Novos clientes por mês</h2>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="chart-wrap">
                    <canvas id="chartClientes" aria-label="Gráfico de novos clientes por mês" role="img"></canvas>
                </div>
                <?php if (empty($clientesData['labels'])): ?>
                    <p class="chart-empty text-muted small text-center mb-0">Nenhum cliente novo para exibir.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between gap-2">
                <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-bolt me-2"></i>Atalhos</h2>
                <a href="<?= e(url('dashboard.index')) ?>" class="small text-decoration-none">Painel</a>
            </div>
            <div class="card-body p-3 p-md-4">
                <?php if ($shortcuts === []): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-square-plus"></i>
                        <p class="mb-0">Nenhum atalho disponível para o seu perfil.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($shortcuts as $shortcut): ?>
                            <?php
                            $shortcutColor = in_array($shortcut['color'] ?? '', $allowedColors, true) ? $shortcut['color'] : 'primary';
                            $shortcutRoute = (string) ($shortcut['route'] ?? '');
                            ?>
                            <div class="col-6">
                                <a href="<?= e(url($shortcutRoute)) ?>" class="shortcut-card text-decoration-none">
                                    <span class="shortcut-icon text-<?= e($shortcutColor) ?>">
                                        <i class="fa-solid fa-<?= e($shortcut['icon'] ?? 'circle') ?>"></i>
                                    </span>
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

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-clock me-2"></i>Próximos agendamentos</h2>
                <a href="<?= e(url('agenda.index')) ?>" class="small text-decoration-none">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted">Tutor / Pet</th>
                            <th class="small text-uppercase text-muted">Quando</th>
                            <th class="small text-uppercase text-muted">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($upcoming === []): ?>
                            <tr><td colspan="3" class="text-center py-5 text-muted"><i class="fa-regular fa-calendar-xmark fa-2x d-block mb-2"></i>Nenhum agendamento próximo.</td></tr>
                        <?php else: ?>
                            <?php foreach ($upcoming as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold small"><?= e($item['nome'] ?? '—') ?></div>
                                        <div class="text-muted small"><?= e($item['pet'] ?? '—') ?> <?= !empty($item['tipo']) ? '· ' . e($item['tipo']) : '' ?></div>
                                    </td>
                                    <td class="small">
                                        <div><?= e(date_br($item['data_agendamento'] ?? null)) ?></div>
                                        <div class="text-muted"><?= e($item['hora_inicio'] ?? '—') ?></div>
                                    </td>
                                    <td><span class="badge bg-petcare-soft text-petcare"><?= e($item['status'] ?? '—') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3">
                <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-clock-rotate-left me-2"></i>Últimas atividades</h2>
            </div>
            <div class="card-body p-3 p-md-4">
                <?php if ($activities === []): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-clipboard"></i>
                        <p class="mb-0">Nenhuma atividade registrada ainda.</p>
                    </div>
                <?php else: ?>
                    <ul class="activity-list list-unstyled mb-0">
                        <?php foreach ($activities as $activity): ?>
                            <li class="activity-item">
                                <span class="activity-dot"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="small fw-semibold"><?= e($activity['acao'] ?? 'Atividade') ?></div>
                                    <div class="small text-muted"><?= e($activity['modulo'] ?? '—') ?> · <?= e($activity['usuario'] ?? 'Sistema') ?></div>
                                </div>
                                <time class="small text-muted text-nowrap"><?= e(date_br($activity['created_at'] ?? null, true)) ?></time>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
