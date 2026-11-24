<?php

$title = $title ?? 'Agenda';
$events = $events ?? [];
$view = $view ?? 'week';
$date = $date ?? date('Y-m-d');
$start = $start ?? $date;
$end = $end ?? $date;
$periodLabel = $periodLabel ?? '';
$status = $status ?? '';
$statusLabel = $statusLabel ?? '';
$stats = $stats ?? ['total' => 0, 'confirmados' => 0, 'aguardando' => 0];
$priorities = $priorities ?? [];

$statusClasses = [
    'confirmado' => 'success',
    'aguardando' => 'warning',
    'em_atendimento' => 'info',
    'finalizado' => 'primary',
    'cancelado' => 'secondary',
    'falta' => 'danger',
];

$viewLabels = ['day' => 'Dia', 'week' => 'Semana', 'month' => 'Mês'];
$statusLabels = [
    'confirmado' => 'Confirmado',
    'aguardando' => 'Aguardando',
    'em_atendimento' => 'Em atendimento',
    'finalizado' => 'Finalizado',
    'cancelado' => 'Cancelado',
    'falta' => 'Não compareceu',
];
$weekdays = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];

$current = new \DateTimeImmutable($date . ' 00:00:00');
$previousDate = $current->modify($view === 'day' ? '-1 day' : ($view === 'week' ? '-7 days' : '-1 month'))->format('Y-m-d');
$nextDate = $current->modify($view === 'day' ? '+1 day' : ($view === 'week' ? '+7 days' : '+1 month'))->format('Y-m-d');

$days = [];
$cursor = new \DateTimeImmutable($start . ' 00:00:00');
$last = new \DateTimeImmutable($end . ' 00:00:00');
while ($cursor <= $last && count($days) < 31) {
    $days[] = $cursor->format('Y-m-d');
    $cursor = $cursor->modify('+1 day');
}

$grouped = [];
foreach ($events as $event) {
    $grouped[substr((string) $event['start'], 0, 10)][] = $event;
}
ksort($grouped);

$renderEvent = static function (array $event) use ($statusClasses, $statusLabels): string {
    $props = $event['extendedProps'] ?? [];
    $status = (string) ($props['status'] ?? '');
    $class = $statusClasses[$status] ?? 'secondary';
    $colorClass = 'bg-' . $class . (in_array($class, ['warning', 'info'], true) ? ' text-dark' : '');
    $label = $statusLabels[$status] ?? $status;
    $time = substr((string) $event['start'], 11, 5) . ' - ' . substr((string) $event['end'], 11, 5);
    $html = '<div class="card border-0 border-start border-4 border-' . e($class) . ' bg-light mb-2 shadow-sm">';
    $html .= '<div class="card-body p-3">';
    $html .= '<div class="d-flex flex-wrap justify-content-between align-items-start gap-2">';
    $html .= '<div class="fw-bold text-petcare small"><i class="fa-regular fa-clock me-1"></i>' . e($time) . '</div>';
    $html .= '<span class="badge rounded-pill ' . e($colorClass) . '">' . e($label) . '</span>';
    $html .= '</div>';
    $html .= '<div class="fw-semibold mt-2">' . e($event['title'] ?? '') . '</div>';
    $html .= '<div class="small text-muted mt-1"><i class="fa-solid fa-user me-1"></i>' . e($props['tutor'] ?? '') . ' <span class="mx-1">·</span> <i class="fa-solid fa-user-doctor me-1"></i>' . e($props['veterinario'] ?? '') . '</div>';
    $html .= '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">';
    $html .= '<span class="small fw-semibold text-petcare">' . e(money($props['valor'] ?? 0)) . '</span>';
    $html .= '<span class="btn-group btn-group-sm">';
    if (can('agenda', 'edit')) {
        $html .= '<a href="' . e(url('agenda.edit', ['id' => $event['id']])) . '" class="btn btn-outline-primary" title="Editar agendamento"><i class="fa-solid fa-pen"></i></a>';
    }
    if (can('atendimentos', 'create')) {
        $html .= '<a href="' . e(url('atendimentos.create', ['agendamento_id' => $event['id']])) . '" class="btn btn-outline-success" title="Registrar atendimento"><i class="fa-solid fa-stethoscope"></i></a>';
    }
    if (can('agenda', 'delete') && in_array($status, ['finalizado', 'cancelado', 'falta'], true)) {
        $html .= render_action('agenda.delete', ['id' => $event['id']], ['label' => 'Excluir', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Excluir este agendamento? Esta ação não pode ser desfeita.']);
    } elseif (can('agenda', 'delete')) {
        $html .= render_action('agenda.destroy', ['id' => $event['id']], ['label' => 'Cancelar', 'icon' => 'circle-xmark', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Cancelar este agendamento?']);
    }
    $html .= '</span></div></div></div>';
    return $html;
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><i class="fa-solid fa-calendar-days me-2"></i>Agenda profissional</h1>
        <p class="text-muted mb-0 small"><?= e($periodLabel) ?></p>
    </div>
    <?php if (can('agenda', 'create')): ?>
        <a href="<?= e(url('agenda.create')) ?>" class="btn btn-petcare shadow-sm"><i class="fa-solid fa-plus me-2"></i>Novo agendamento</a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="btn-group" role="group" aria-label="Visualização da agenda">
            <?php foreach ($viewLabels as $key => $label): ?>
                <a href="<?= e(url('agenda.index', array_filter(['view' => $key, 'date' => $date, 'status' => $status]))) ?>" class="btn btn-sm <?= $view === $key ? 'btn-petcare' : 'btn-outline-secondary' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= e(url('agenda.index', array_filter(['view' => $view, 'date' => $previousDate, 'status' => $status]))) ?>" class="btn btn-sm btn-outline-secondary" aria-label="Período anterior"><i class="fa-solid fa-chevron-left"></i></a>
            <a href="<?= e(url('agenda.index', array_filter(['view' => 'day', 'date' => date('Y-m-d'), 'status' => $status]))) ?>" class="btn btn-sm btn-outline-petcare">Hoje</a>
            <a href="<?= e(url('agenda.index', array_filter(['view' => $view, 'date' => $nextDate, 'status' => $status]))) ?>" class="btn btn-sm btn-outline-secondary" aria-label="Próximo período"><i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <form method="get" action="<?= e(url('agenda.index')) ?>" class="d-flex align-items-center gap-2">
            <input type="hidden" name="r" value="agenda.index">
            <input type="hidden" name="view" value="<?= e($view) ?>">
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <label for="status" class="small text-muted mb-0">Status</label>
            <select name="status" id="status" class="form-select form-select-sm w-auto">
                <option value="">Todos</option>
                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-petcare"><i class="fa-solid fa-filter me-1"></i>Filtrar</button>
            <?php if ($status !== ''): ?>
                <a href="<?= e(url('agenda.index', ['view' => $view, 'date' => $date])) ?>" class="btn btn-sm btn-link text-decoration-none">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-9">
        <?php if ($view === 'day'): ?>
            <div class="card border-0 shadow-petcare">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-clock me-2"></i><?= e(date_br($date)) ?></h2>
                    <span class="badge bg-petcare-soft rounded-pill"><?= count($events) ?> agendamento(s)</span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <?php for ($hour = 7; $hour <= 19; $hour++): ?>
                        <?php
                        $slot = sprintf('%02d:00', $hour);
                        $nextSlot = sprintf('%02d:00', $hour + 1);
                        $slotEvents = array_values(array_filter($events, static function (array $event) use ($slot, $nextSlot): bool {
                            $eventTime = substr((string) $event['start'], 11, 5);
                            return $eventTime >= $slot && $eventTime < $nextSlot;
                        }));
                        ?>
                        <div class="row g-2 border-bottom py-3">
                            <div class="col-2 col-md-1 fw-bold text-muted small"><?= e($slot) ?></div>
                            <div class="col-10 col-md-11">
                                <?php if ($slotEvents === []): ?>
                                    <div class="text-muted small fst-italic">Sem agendamentos</div>
                                <?php else: ?>
                                    <?php foreach ($slotEvents as $event): ?>
                                        <?= $renderEvent($event) ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                    <?php
                    $otherEvents = array_values(array_filter($events, static function (array $event): bool {
                        $eventTime = substr((string) $event['start'], 11, 5);
                        return $eventTime < '07:00' || $eventTime >= '20:00';
                    }));
                    ?>
                    <?php if ($otherEvents !== []): ?>
                        <div class="row g-2 py-3">
                            <div class="col-2 col-md-1 fw-bold text-muted small">Outros</div>
                            <div class="col-10 col-md-11">
                                <?php foreach ($otherEvents as $event): ?>
                                    <?= $renderEvent($event) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php if ($days === []): ?>
                <div class="card border-0 shadow-petcare"><div class="card-body text-center py-5 text-muted"><i class="fa-regular fa-calendar-xmark fa-2x d-block mb-2"></i>Nenhum dia disponível no período.</div></div>
            <?php endif; ?>
            <?php foreach ($days as $day): ?>
                <?php
                $dayDate = new \DateTimeImmutable($day . ' 00:00:00');
                $dayEvents = $grouped[$day] ?? [];
                ?>
                <div class="card border-0 shadow-petcare mb-3">
                    <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-regular fa-calendar me-2"></i><?= e($weekdays[(int) $dayDate->format('N')] . ', ' . date_br($day)) ?></h2>
                        <span class="badge bg-petcare-soft rounded-pill"><?= count($dayEvents) ?> agendamento(s)</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <?php if ($dayEvents === []): ?>
                            <div class="text-center py-4 text-muted"><i class="fa-regular fa-calendar-xmark fa-2x d-block mb-2"></i>Nenhum agendamento neste dia.</div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($dayEvents as $event): ?>
                                    <div class="col-md-6"><?= $renderEvent($event) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="col-xl-3">
        <div class="card border-0 shadow-petcare mb-3">
            <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-chart-simple me-2"></i>Resumo do período</h2></div>
            <div class="card-body p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span class="small text-muted">Total</span><strong class="text-petcare"><?= (int) $stats['total'] ?></strong></div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span class="small text-muted">Confirmados</span><span class="badge bg-success rounded-pill"><?= (int) $stats['confirmados'] ?></span></div>
                <div class="d-flex justify-content-between align-items-center py-2"><span class="small text-muted">Aguardando</span><span class="badge bg-warning text-dark rounded-pill"><?= (int) $stats['aguardando'] ?></span></div>
            </div>
        </div>

        <div class="card border-0 shadow-petcare">
            <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-star me-2"></i>Próximos agendamentos</h2></div>
            <div class="list-group list-group-flush">
                <?php if ($priorities === []): ?>
                    <div class="text-center py-4 text-muted small"><i class="fa-regular fa-calendar-check fa-2x d-block mb-2"></i>Nenhum agendamento futuro.</div>
                <?php else: ?>
                    <?php foreach ($priorities as $priority): ?>
                        <a href="<?= e(url('agenda.edit', ['id' => $priority['id']])) ?>" class="list-group-item list-group-item-action py-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold small"><?= e($priority['pet']) ?> · <?= e($priority['tipo']) ?></div>
                                    <div class="text-muted small"><?= e(date_br((string) $priority['data_agendamento'])) ?> · <?= e(substr((string) $priority['hora_inicio'], 0, 5)) ?></div>
                                    <div class="text-muted small"><?= e($priority['veterinario']) ?></div>
                                </div>
                                <?= status_badge($priority['status'] ?? null) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
