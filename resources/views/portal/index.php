<?php
$title = $title ?? 'Portal do Tutor';
$cliente = $cliente ?? [];
$animais = $animais ?? [];
$agendamentos = $agendamentos ?? [];
$vacinas = $vacinas ?? [];
$saldo = (float) ($saldo ?? 0);
?>
<div class="mb-4">
    <h1 class="h3 fw-bold text-petcare mb-1">Olá, <?= e($cliente['nome'] ?? 'Tutor') ?></h1>
    <p class="text-muted mb-0">Acompanhe seus animais, agendamentos e pendências.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><div class="card border-0 shadow-petcare"><div class="card-body"><div class="text-muted small">Animais</div><div class="fs-3 fw-bold text-petcare"><?= count($animais) ?></div></div></div></div>
    <div class="col-sm-6 col-lg-3"><div class="card border-0 shadow-petcare"><div class="card-body"><div class="text-muted small">Próximos agendamentos</div><div class="fs-3 fw-bold text-petcare"><?= count($agendamentos) ?></div></div></div></div>
    <div class="col-sm-6 col-lg-3"><div class="card border-0 shadow-petcare"><div class="card-body"><div class="text-muted small">Vacinas a vencer</div><div class="fs-3 fw-bold text-petcare"><?= count($vacinas) ?></div></div></div></div>
    <div class="col-sm-6 col-lg-3"><div class="card border-0 shadow-petcare"><div class="card-body"><div class="text-muted small">Saldo em aberto</div><div class="fs-3 fw-bold text-petcare"><?= e(money($saldo)) ?></div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between"><h2 class="h5 mb-0">Meus animais</h2><a href="<?= e(url('portal.animais')) ?>" class="small text-decoration-none">Ver todos</a></div>
            <div class="card-body p-4">
                <?php if ($animais === []): ?>
                    <p class="text-muted mb-0">Nenhum animal cadastrado.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($animais, 0, 5) as $a): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between"><span><?= e($a['nome']) ?></span><span class="text-muted small"><?= e($a['especie'] ?? '') ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between"><h2 class="h5 mb-0">Próximos agendamentos</h2><a href="<?= e(url('portal.agendamentos')) ?>" class="small text-decoration-none">Ver todos</a></div>
            <div class="card-body p-4">
                <?php if ($agendamentos === []): ?>
                    <p class="text-muted mb-0">Nenhum agendamento futuro.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($agendamentos as $ag): ?>
                            <li class="list-group-item px-0">
                                <div class="fw-semibold"><?= e($ag['animal']) ?> - <?= e($ag['tipo']) ?></div>
                                <div class="text-muted small"><?= e(date_br((string) $ag['data_agendamento'])) ?> <?= e(substr((string) $ag['hora_inicio'], 0, 5)) ?> · <?= status_badge((string) $ag['status']) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-petcare">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between"><h2 class="h5 mb-0">Vacinas a vencer</h2><a href="<?= e(url('portal.vacinas')) ?>" class="small text-decoration-none">Ver todas</a></div>
            <div class="card-body p-4">
                <?php if ($vacinas === []): ?>
                    <p class="text-muted mb-0">Nenhuma vacina a vencer nos próximos 30 dias.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Animal</th><th>Vacina</th><th>Próxima dose</th></tr></thead>
                            <tbody>
                                <?php foreach ($vacinas as $v): ?>
                                    <tr><td><?= e($v['animal']) ?></td><td><?= e($v['vacina']) ?></td><td><?= e(date_br((string) $v['proxima_dose'])) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
