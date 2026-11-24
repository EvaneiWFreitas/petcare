<?php

$animal = $animal ?? [];
$tutor = $tutor ?? [];
$vacinacoes = $vacinacoes ?? [];
$title = $title ?? 'Carteira de vacinação';
$animalId = (int) ($animal['id'] ?? 0);
?>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3 p-md-4 border-bottom border-4 border-petcare">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-petcare text-white" style="width:56px;height:56px;font-size:1.6rem"><i class="fa-solid fa-paw"></i></span>
                <div>
                    <div class="fw-bold fs-4 text-petcare mb-0">PetCare</div>
                    <div class="text-muted small">Carteirinha digital de vacinação</div>
                </div>
            </div>
            <div class="no-print d-flex gap-2 flex-wrap">
                <a href="<?= e(url('vacinas.print', ['animal_id' => $animalId])) ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-file-arrow-down me-1"></i>Abrir versão para impressão</a>
                <button type="button" class="btn btn-petcare btn-sm shadow-sm" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Imprimir / Salvar PDF</button>
            </div>
        </div>
    </div>
    <div class="card-body p-3 p-md-4">
        <div class="row g-3">
            <div class="col-md-6">
                <h2 class="h6 fw-bold text-petcare text-uppercase"><i class="fa-solid fa-dog me-2"></i>Dados do pet</h2>
                <dl class="row mb-0 small">
                    <dt class="col-sm-5 text-muted">Nome</dt><dd class="col-sm-7"><?= e($animal['nome'] ?? '') ?></dd>
                    <dt class="col-sm-5 text-muted">Código</dt><dd class="col-sm-7"><?= e($animal['codigo'] ?? '') ?></dd>
                    <dt class="col-sm-5 text-muted">Nascimento</dt><dd class="col-sm-7"><?= e(date_br($animal['data_nascimento'] ?? null)) ?></dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 fw-bold text-petcare text-uppercase"><i class="fa-solid fa-user me-2"></i>Dados do tutor</h2>
                <dl class="row mb-0 small">
                    <dt class="col-sm-5 text-muted">Nome</dt><dd class="col-sm-7"><?= e($tutor['nome'] ?? '') ?></dd>
                    <dt class="col-sm-5 text-muted">Telefone</dt><dd class="col-sm-7"><?= e($tutor['telefone'] ?? '') ?></dd>
                    <dt class="col-sm-5 text-muted">E-mail</dt><dd class="col-sm-7"><?= e($tutor['email'] ?? '') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-syringe me-2"></i>Doses aplicadas</h2>
        <span class="badge bg-petcare-soft rounded-pill"><?= count($vacinacoes) ?> dose(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Vacina</th>
                    <th class="small text-uppercase text-muted fw-bold">Fabricante</th>
                    <th class="small text-uppercase text-muted fw-bold">Lote</th>
                    <th class="small text-uppercase text-muted fw-bold">Aplicação</th>
                    <th class="small text-uppercase text-muted fw-bold">Próxima dose</th>
                    <th class="small text-uppercase text-muted fw-bold">Veterinário</th>
                    <th class="small text-uppercase text-muted fw-bold">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($vacinacoes === []): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-regular fa-folder-open fa-2x d-block mb-2"></i>Nenhuma dose registrada para este pet.</td></tr>
                <?php else: ?>
                    <?php foreach ($vacinacoes as $vacinacao): ?>
                        <tr>
                            <td class="small fw-semibold"><?= e($vacinacao['vacina'] ?? '') ?></td>
                            <td class="small"><?= e($vacinacao['fabricante'] ?? '') ?></td>
                            <td class="small"><?= e($vacinacao['lote'] ?? '') ?></td>
                            <td class="small"><?= e(date_br($vacinacao['data_aplicacao'] ?? null)) ?></td>
                            <td class="small"><?= e(date_br($vacinacao['proxima_dose'] ?? null)) ?></td>
                            <td class="small"><?= e($vacinacao['veterinario'] ?? '') ?></td>
                            <td><span class="badge bg-<?= e($vacinacao['status_class'] ?? 'secondary') ?> rounded-pill"><?= e($vacinacao['status_label'] ?? '') ?></span></td>
                        </tr>
                        <?php if (!empty($vacinacao['observacoes'])): ?>
                            <tr><td colspan="7" class="small text-muted ps-4"><?= e($vacinacao['observacoes']) ?></td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
