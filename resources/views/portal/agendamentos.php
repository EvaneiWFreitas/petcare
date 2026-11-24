<?php
$title = $title ?? 'Meus agendamentos';
$rows = $rows ?? [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Acompanhe e solicite agendamentos.</p>
    </div>
    <a href="<?= e(url('portal.solicitar')) ?>" class="btn btn-petcare"><i class="fa-solid fa-calendar-plus me-2"></i>Solicitar agendamento</a>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th><th>Data</th><th>Horário</th><th>Animal</th><th>Veterinário</th><th>Tipo</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Nenhum agendamento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['codigo'] ?? '') ?></td>
                            <td><?= e(date_br((string) ($row['data_agendamento'] ?? ''))) ?></td>
                            <td><?= e(substr((string) ($row['hora_inicio'] ?? ''), 0, 5)) ?><?php if (!empty($row['hora_fim'])): ?> - <?= e(substr((string) $row['hora_fim'], 0, 5)) ?><?php endif; ?></td>
                            <td><?= e($row['animal'] ?? '') ?></td>
                            <td><?= e($row['veterinario'] ?? '—') ?></td>
                            <td><?= e($row['tipo'] ?? '') ?></td>
                            <td><?= status_badge((string) ($row['status'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
