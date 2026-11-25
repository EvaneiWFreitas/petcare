<?php include __DIR__ . '/_nav.php'; ?>

<?php
$total = count($licencas);
$ativas = count(array_filter($licencas, static fn(array $row): bool => ($row['status_efetivo'] ?? '') === 'ativa'));
$expiradas = count(array_filter($licencas, static fn(array $row): bool => ($row['status_efetivo'] ?? '') === 'expirada'));
$ilimitadas = count(array_filter($licencas, static fn(array $row): bool => !empty($row['permanente'])));
$bloqueadas = count(array_filter($licencas, static fn(array $row): bool => !empty($row['bloqueada'])));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare">Visão geral do licenciamento</h1>
        <p class="text-muted mb-0 small">Controle de prazo, bloqueios e situação das instalações PetCare.</p>
    </div>
    <a href="<?= e(url('licenca.configuracao')) ?>" class="btn btn-petcare shadow-sm">
        <i class="fa-solid fa-clock me-2"></i>Configurar prazo e bloqueio
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-petcare h-100 licenca-stat">
            <div class="card-body">
                <span class="licenca-stat-icon bg-petcare-soft text-petcare"><i class="fa-solid fa-building"></i></span>
                <div class="small text-muted mt-3">Total de instalações</div>
                <div class="h4 mb-0 fw-bold"><?= $total ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-petcare h-100 licenca-stat">
            <div class="card-body">
                <span class="licenca-stat-icon bg-success-subtle text-success"><i class="fa-solid fa-circle-check"></i></span>
                <div class="small text-muted mt-3">Licenças ativas</div>
                <div class="h4 mb-0 fw-bold"><?= $ativas ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-petcare h-100 licenca-stat">
            <div class="card-body">
                <span class="licenca-stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div class="small text-muted mt-3">Expiradas / bloqueadas</div>
                <div class="h4 mb-0 fw-bold"><?= $expiradas + $bloqueadas ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-petcare h-100 licenca-stat">
            <div class="card-body">
                <span class="licenca-stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-infinity"></i></span>
                <div class="small text-muted mt-3">Licenças ilimitadas</div>
                <div class="h4 mb-0 fw-bold"><?= $ilimitadas ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="small text-uppercase text-muted fw-bold">Instalação local</div>
                <h2 class="h5 mb-1 fw-bold"><?= e($status['clinica']['nome'] ?? 'PetCare') ?></h2>
                <div class="text-muted small"><?= e($status['clinica']['codigo'] ?? '—') ?> · <?= e($status['clinica']['responsavel'] ?? '—') ?></div>
            </div>
            <div class="text-md-end">
                <?= license_badge($status['status'] ?? null) ?>
                <div class="small text-muted mt-2">
                    <?php if (!empty($status['permanente'])): ?>
                        Sem vencimento
                    <?php elseif ($status['vencimento'] ?? null): ?>
                        Vencimento: <strong><?= e(date_br((string) $status['vencimento'])) ?></strong>
                        <?php if (($status['dias_restantes'] ?? null) !== null): ?>
                            · <strong><?= (int) $status['dias_restantes'] ?> dia(s)</strong>
                        <?php endif; ?>
                        <?php if (!empty($block_date)): ?>
                            <div class="mt-1">Bloqueio programado: <strong><?= e(date_br((string) $block_date)) ?></strong></div>
                        <?php endif; ?>
                    <?php else: ?>
                        Aguardando ativação
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!$status['allowed'] && is_superadmin()): ?>
            <div class="alert alert-warning border-0 mt-3 mb-0 small" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i>
                A operação da clínica está bloqueada. Ative ou prorrogue uma licença para liberar o uso.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center p-3 p-md-4">
        <h2 class="h6 mb-0 fw-bold">Instalações cadastradas</h2>
        <span class="small text-muted">Prazo inicial configurado: <?= (int) ($settings['dias_iniciais'] ?? 30) ?> dias</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Clínica</th>
                    <th class="small text-uppercase text-muted fw-bold">Modalidade</th>
                    <th class="small text-uppercase text-muted fw-bold">Ativação</th>
                    <th class="small text-uppercase text-muted fw-bold">Vencimento</th>
                    <th class="small text-uppercase text-muted fw-bold">Situação</th>
                    <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($licencas === []): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Nenhuma instalação licenciada.</td></tr>
                <?php else: ?>
                    <?php foreach ($licencas as $row): ?>
                        <?php $id = (int) ($row['id'] ?? 0); ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($row['clinica_nome'] ?? '—') ?></div>
                                <div class="small text-muted"><?= e($row['clinica_codigo'] ?? '—') ?> · <?= e($row['clinica_responsavel'] ?? '—') ?></div>
                                <div class="small text-muted">Licença <?= e($row['codigo_licenca'] ?? '—') ?></div>
                            </td>
                            <td><?= e($row['modalidade_label'] ?? '—') ?></td>
                            <td><?= e(date_br($row['data_ativacao'] ?? null)) ?></td>
                            <td>
                                <?php if (!empty($row['permanente'])): ?>
                                    <span class="text-muted">Sem vencimento</span>
                                <?php else: ?>
                                    <?= e(date_br($row['data_vencimento'] ?? null)) ?>
                                    <?php if (($row['dias_restantes'] ?? null) !== null): ?>
                                        <div class="small <?= (int) $row['dias_restantes'] <= 3 ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= (int) $row['dias_restantes'] ?> dia(s) restante(s)</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= license_badge($row['status_efetivo'] ?? null) ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    <?php if (!empty($row['bloqueada'])): ?>
                                        <form method="post" action="<?= e(url('licenca.ativar')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="clinica_id" value="<?= $id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Ativar 30 dias">
                                                <i class="fa-solid fa-play"></i><span class="d-none d-sm-inline ms-1">Ativar</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= e(url('licenca.prorrogacao', ['clinica_id' => $id])) ?>" class="btn btn-sm btn-outline-primary" title="Prorrogar">
                                        <i class="fa-solid fa-calendar-plus"></i><span class="d-none d-sm-inline ms-1">Prorrogar</span>
                                    </a>
                                    <a href="<?= e(url('licenca.historico', ['licenca_id' => $id])) ?>" class="btn btn-sm btn-outline-secondary" title="Histórico">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
