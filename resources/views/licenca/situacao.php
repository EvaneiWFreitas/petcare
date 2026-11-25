<?php

use PetCare\Services\LicenseService;

include __DIR__ . '/_nav.php';

$avisos = LicenseService::settings()['avisos'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare">Situação dos sistemas licenciados</h1>
        <p class="text-muted mb-0 small">Diagnóstico atual de cada instalação e do bloqueio automático.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-body p-3 p-md-4">
                <div class="small text-uppercase text-muted fw-bold">Licença da instalação local</div>
                <div class="d-flex align-items-center gap-2 mt-2 mb-3">
                    <?= license_badge($status['status'] ?? null) ?>
                    <span class="small text-muted"><?= e($status['modalidade'] ?? '—') ?></span>
                </div>
                <dl class="row small mb-0">
                    <dt class="col-sm-4 text-muted">Clínica</dt>
                    <dd class="col-sm-8"><?= e($status['clinica']['nome'] ?? '—') ?></dd>
                    <dt class="col-sm-4 text-muted">Vencimento</dt>
                    <dd class="col-sm-8"><?= !empty($status['permanente']) ? 'Sem vencimento' : e(date_br($status['vencimento'] ?? null)) ?></dd>
                    <dt class="col-sm-4 text-muted">Dias restantes</dt>
                    <dd class="col-sm-8"><?= ($status['dias_restantes'] ?? null) !== null ? (int) $status['dias_restantes'] : '—' ?></dd>
                    <dt class="col-sm-4 text-muted">Bloqueio automático</dt>
                    <dd class="col-sm-8"><?= !empty($status['allowed']) ? '<span class="text-success fw-semibold">Desativado (licença válida)</span>' : '<span class="text-danger fw-semibold">Ativado</span>' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-body p-3 p-md-4">
                <div class="small text-uppercase text-muted fw-bold">Regras aplicadas</div>
                <ul class="small text-muted mb-0 mt-3 ps-3">
                    <li>Dias de aviso: <?= e(implode(', ', array_map('strval', $avisos))) ?></li>
                    <li>Licença vencida bloqueia cadastros, consultas, agendamentos, prontuários e relatórios.</li>
                    <li>O proprietário continua com acesso ao módulo de licenciamento.</li>
                    <li>Os dados da clínica são preservados durante o bloqueio.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Sistema</th>
                    <th class="small text-uppercase text-muted fw-bold">Licença</th>
                    <th class="small text-uppercase text-muted fw-bold">Modalidade</th>
                    <th class="small text-uppercase text-muted fw-bold">Situação</th>
                    <th class="small text-uppercase text-muted fw-bold">Acesso</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($licencas === []): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Nenhum sistema licenciado.</td></tr>
                <?php else: ?>
                    <?php foreach ($licencas as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($row['clinica_nome'] ?? '—') ?></div>
                                <div class="small text-muted"><?= !empty($row['local_instalacao']) ? 'Instalação local' : 'Instalação remota' ?></div>
                            </td>
                            <td><?= e($row['codigo_licenca'] ?? '—') ?></td>
                            <td><?= e($row['modalidade_label'] ?? '—') ?></td>
                            <td><?= license_badge($row['status_efetivo'] ?? null) ?></td>
                            <td>
                                <?php if (!empty($row['bloqueada'])): ?>
                                    <span class="badge bg-danger-subtle text-danger">Bloqueado</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success">Liberado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
