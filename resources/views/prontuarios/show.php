<?php

$title = $title ?? 'Detalhes do prontuário';
$sections = $sections ?? [];
$actions = $actions ?? [];
$recordId = $recordId ?? 0;
$prontuario = $prontuario ?? [];
$historico = $historico ?? [];
$auditorias = $auditorias ?? [];
$anexos = $anexos ?? [];

ob_start();
require __DIR__ . '/../common/detail.php';
$html = (string) ob_get_clean();
$from = 'href="' . e(url('prontuarios.edit')) . '"';
$to = 'href="' . e(url('prontuarios.edit', ['id' => (int) $recordId])) . '"';
$html = str_replace($from, $to, $html);

$finalizado = (int) ($prontuario['finalizado'] ?? 0) === 1;
$badge = $finalizado ? status_badge('finalizado') : '<span class="badge bg-secondary rounded-pill">Em aberto</span>';
?>

<div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div><i class="fa-solid fa-shield-halved me-2"></i><strong>Rastreabilidade ativa:</strong> alterações neste prontuário são registradas em auditoria.</div>
        <?= $badge ?>
    </div>
</div>

<?= $html ?>

<div class="card border-0 shadow-petcare mt-4">
    <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-clock-rotate-left me-2"></i>Histórico clínico do animal</h2>
        <span class="badge bg-petcare-soft rounded-pill"><?= count($historico) ?> registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Data</th>
                    <th class="small text-uppercase text-muted fw-bold">Veterinário</th>
                    <th class="small text-uppercase text-muted fw-bold">Diagnóstico</th>
                    <th class="small text-uppercase text-muted fw-bold">Situação</th>
                    <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($historico === []): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum prontuário registrado para este animal.</td></tr>
                <?php else: ?>
                    <?php foreach ($historico as $item): ?>
                        <tr>
                            <td><?= e(date_br((string) $item['data_atendimento'], true)) ?></td>
                            <td><?= e($item['veterinario'] ?? '') ?></td>
                            <td><small><?= e(mb_strimwidth((string) ($item['diagnostico'] ?? $item['suspeita_clinica'] ?? ''), 0, 70, '...')) ?></small></td>
                            <td>
                                <?php if ((int) ($item['finalizado'] ?? 0) === 1): ?>
                                    <?= status_badge('finalizado') ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill">Em aberto</span>
                                <?php endif; ?>
                                <?php if ((int) ($item['id'] ?? 0) === (int) $recordId): ?>
                                    <span class="badge bg-petcare-soft rounded-pill ms-1">Atual</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><a href="<?= e(url('prontuarios.show', ['id' => $item['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Visualizar prontuário"><i class="fa-solid fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-user-shield me-2"></i>Auditoria de alterações</h2></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Data</th>
                            <th class="small text-uppercase text-muted fw-bold">Usuário</th>
                            <th class="small text-uppercase text-muted fw-bold">Ação</th>
                            <th class="small text-uppercase text-muted fw-bold">Justificativa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($auditorias === []): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Nenhuma alteração registrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($auditorias as $auditoria): ?>
                                <tr>
                                    <td><?= e(date_br((string) $auditoria['created_at'], true)) ?></td>
                                    <td><?= e($auditoria['usuario'] ?? '') ?></td>
                                    <td><small><?= e(str_replace('_', ' ', (string) $auditoria['acao'])) ?></small></td>
                                    <td><small><?= e(mb_strimwidth((string) ($auditoria['justificativa'] ?? ''), 0, 60, '...')) ?></small></td>
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
            <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-paperclip me-2"></i>Anexos</h2></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Arquivo</th>
                            <th class="small text-uppercase text-muted fw-bold">Tamanho</th>
                            <th class="small text-uppercase text-muted fw-bold">Enviado em</th>
                            <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($anexos === []): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Nenhum anexo enviado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($anexos as $anexo): ?>
                                <tr>
                                    <td><small><?= e($anexo['nome_original'] ?? '') ?></small></td>
                                    <td><small><?= e(number_format((int) ($anexo['tamanho'] ?? 0) / 1024, 1, ',', '.') . ' KB') ?></small></td>
                                    <td><small><?= e(date_br((string) $anexo['created_at'], true)) ?></small></td>
                                    <td class="text-end"><a href="<?= e(url('anexos.download', ['id' => $anexo['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Baixar anexo"><i class="fa-solid fa-download"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
