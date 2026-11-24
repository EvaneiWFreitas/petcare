<?php

$cirurgia = $cirurgia ?? [];
$statusOptions = $statusOptions ?? [];
$title = $title ?? 'Detalhes da cirurgia';
$sections = $sections ?? [];
$actions = $actions ?? [];
$recordId = $recordId ?? (int) ($cirurgia['id'] ?? 0);

ob_start();
require __DIR__ . '/../common/detail.php';
$html = (string) ob_get_clean();
?>

<?= $html ?>

<div class="card border-0 shadow-petcare mt-4">
    <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-pen-to-square me-2"></i>Atualizar cirurgia</h2></div>
    <div class="card-body p-3 p-md-4">
        <form method="post" action="<?= e(url('cirurgias.update')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($recordId) ?>">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="field_procedimento" class="form-label small fw-semibold">Procedimento realizado</label>
                    <textarea name="procedimento_realizado" id="field_procedimento" rows="3" class="form-control"><?= e($cirurgia['procedimento_realizado'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="field_evolucao" class="form-label small fw-semibold">Evolução pós-operatória</label>
                    <textarea name="evolucao_pos_operatoria" id="field_evolucao" rows="4" class="form-control"><?= e($cirurgia['evolucao_pos_operatoria'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="field_recomendacoes" class="form-label small fw-semibold">Recomendações</label>
                    <textarea name="recomendacoes" id="field_recomendacoes" rows="4" class="form-control"><?= e($cirurgia['recomendacoes'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="field_retorno" class="form-label small fw-semibold">Retorno</label>
                    <input type="date" name="retorno" id="field_retorno" class="form-control" value="<?= e($cirurgia['retorno'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="field_status" class="form-label small fw-semibold">Status</label>
                    <select name="status" id="field_status" class="form-select">
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected($cirurgia['status'] ?? '', $value) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-3 text-end"><button type="submit" class="btn btn-petcare"><i class="fa-solid fa-floppy-disk me-2"></i>Salvar alterações</button></div>
        </form>
    </div>
</div>
