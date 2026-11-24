<?php

$internacao = $internacao ?? [];
$evolucoes = $evolucoes ?? [];
$title = $title ?? 'Detalhes da internação';
$sections = $sections ?? [];
$actions = $actions ?? [];
$recordId = $recordId ?? (int) ($internacao['id'] ?? 0);
$alta = ($internacao['status'] ?? '') === 'alta' || !empty($internacao['data_alta']);

ob_start();
require __DIR__ . '/../common/detail.php';
$html = (string) ob_get_clean();
?>

<?= $html ?>

<?php if ($alta): ?>
    <div class="alert alert-secondary border-0 shadow-sm mt-4"><i class="fa-solid fa-circle-check me-2"></i>Esta internação foi encerrada com alta em <?= e(date_br($internacao['data_alta'], true)) ?>.</div>
<?php else: ?>
    <div class="row g-4 mt-1">
        <div class="col-lg-7">
            <div class="card border-0 shadow-petcare h-100">
                <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-notes-medical me-2"></i>Nova evolução</h2></div>
                <div class="card-body p-3 p-md-4">
                    <form method="post" action="<?= e(url('internacoes.evolve')) ?>" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($recordId) ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="field_data_evolucao" class="form-label small fw-semibold">Data e hora</label>
                                <input type="datetime-local" name="data_evolucao" id="field_data_evolucao" class="form-control" value="<?= e(date('Y-m-d\TH:i')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="field_alimentacao" class="form-label small fw-semibold">Alimentação</label>
                                <input type="text" name="alimentacao" id="field_alimentacao" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="field_hidratacao" class="form-label small fw-semibold">Hidratação</label>
                                <input type="text" name="hidratacao" id="field_hidratacao" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="field_sinais_vitais" class="form-label small fw-semibold">Sinais vitais</label>
                                <input type="text" name="sinais_vitais" id="field_sinais_vitais" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="field_medicamentos" class="form-label small fw-semibold">Medicamentos</label>
                                <textarea name="medicamentos" id="field_medicamentos" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="field_intercorrencias" class="form-label small fw-semibold">Intercorrências</label>
                                <textarea name="intercorrencias" id="field_intercorrencias" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="mt-3 text-end"><button type="submit" class="btn btn-petcare"><i class="fa-solid fa-plus me-2"></i>Registrar evolução</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-petcare h-100">
                <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-house-chimney-medical me-2"></i>Registrar alta</h2></div>
                <div class="card-body p-3 p-md-4">
                    <form method="post" action="<?= e(url('internacoes.discharge')) ?>" onsubmit="return confirm('Confirma a alta desta internação?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($recordId) ?>">
                        <div class="mb-3">
                            <label for="field_data_alta" class="form-label small fw-semibold">Data e hora da alta</label>
                            <input type="datetime-local" name="data_alta" id="field_data_alta" class="form-control" value="<?= e(date('Y-m-d\TH:i')) ?>">
                        </div>
                        <div class="alert alert-warning small py-2"><i class="fa-solid fa-triangle-exclamation me-1"></i>Ao confirmar, a internação será encerrada e não poderá receber novas evoluções.</div>
                        <div class="text-end"><button type="submit" class="btn btn-outline-success"><i class="fa-solid fa-check me-2"></i>Confirmar alta</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-petcare mt-4">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-clock-rotate-left me-2"></i>Histórico de evoluções</h2>
        <span class="badge bg-petcare-soft rounded-pill"><?= count($evolucoes) ?> registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Data</th>
                    <th class="small text-uppercase text-muted fw-bold">Responsável</th>
                    <th class="small text-uppercase text-muted fw-bold">Alimentação</th>
                    <th class="small text-uppercase text-muted fw-bold">Hidratação</th>
                    <th class="small text-uppercase text-muted fw-bold">Sinais vitais</th>
                    <th class="small text-uppercase text-muted fw-bold">Medicamentos</th>
                    <th class="small text-uppercase text-muted fw-bold">Intercorrências</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($evolucoes === []): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Nenhuma evolução registrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($evolucoes as $evolucao): ?>
                        <tr>
                            <td class="small"><?= e(date_br($evolucao['data_evolucao'] ?? null, true)) ?></td>
                            <td class="small"><?= e($evolucao['usuario'] ?? '') ?></td>
                            <td class="small"><?= e($evolucao['alimentacao'] ?? '') ?></td>
                            <td class="small"><?= e($evolucao['hidratacao'] ?? '') ?></td>
                            <td class="small"><?= e($evolucao['sinais_vitais'] ?? '') ?></td>
                            <td class="small"><?= e($evolucao['medicamentos'] ?? '') ?></td>
                            <td class="small"><?= e($evolucao['intercorrencias'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
