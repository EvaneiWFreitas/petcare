<?php

$exame = $exame ?? [];
$resultados = $resultados ?? [];
$anexos = $anexos ?? [];
$statusOptions = $statusOptions ?? ['solicitado' => 'Solicitado', 'liberado' => 'Liberado'];
$title = $title ?? 'Detalhes do exame';
$sections = $sections ?? [];
$actions = $actions ?? [];
$recordId = $recordId ?? (int) ($exame['id'] ?? 0);

ob_start();
require __DIR__ . '/../common/detail.php';
$html = (string) ob_get_clean();
?>

<?= $html ?>

<div class="card border-0 shadow-petcare mt-4">
    <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-flask me-2"></i>Resultados estruturados</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Descrição</th>
                    <th class="small text-uppercase text-muted fw-bold">Valor</th>
                    <th class="small text-uppercase text-muted fw-bold">Unidade</th>
                    <th class="small text-uppercase text-muted fw-bold">Referência</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultados === []): ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted">Nenhum resultado registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($resultados as $resultado): ?>
                        <tr>
                            <td class="small"><?= e($resultado['descricao'] ?? '') ?></td>
                            <td class="small"><?= e($resultado['valor'] ?? '') ?></td>
                            <td class="small"><?= e($resultado['unidade'] ?? '') ?></td>
                            <td class="small"><?= e($resultado['referencia'] ?? '') ?></td>
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
            <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-file-circle-check me-2"></i>Registrar resultado</h2></div>
            <div class="card-body p-3 p-md-4">
                <form method="post" action="<?= e(url('exames.result')) ?>" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= e($recordId) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="field_data_resultado" class="form-label small fw-semibold">Data do resultado</label>
                            <input type="date" name="data_resultado" id="field_data_resultado" class="form-control" value="<?= e($exame['data_resultado'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="field_status" class="form-label small fw-semibold">Status</label>
                            <select name="status" id="field_status" class="form-select">
                                <?php foreach ($statusOptions as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= selected($exame['status'] ?? '', $value) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="field_resultados" class="form-label small fw-semibold">Resultados</label>
                            <textarea name="resultados" id="field_resultados" rows="4" class="form-control"><?= e($exame['resultados'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-semibold mb-0">Itens do resultado</label>
                                <button type="button" id="add-resultado" class="btn btn-outline-petcare btn-sm"><i class="fa-solid fa-plus me-1"></i>Adicionar item</button>
                            </div>
                            <div id="resultados-itens">
                                <div class="row g-2 mb-2 resultado-item">
                                    <div class="col-md-4"><input type="text" name="resultado_descricao[]" class="form-control form-control-sm" placeholder="Descrição"></div>
                                    <div class="col-md-2"><input type="text" name="resultado_valor[]" class="form-control form-control-sm" placeholder="Valor"></div>
                                    <div class="col-md-2"><input type="text" name="resultado_unidade[]" class="form-control form-control-sm" placeholder="Unidade"></div>
                                    <div class="col-md-3"><input type="text" name="resultado_referencia[]" class="form-control form-control-sm" placeholder="Referência"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.resultado-item').remove()"><i class="fa-solid fa-trash"></i></button></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label for="field_anexo" class="form-label small fw-semibold">Anexar laudo (opcional)</label>
                            <input type="file" name="anexo" id="field_anexo" class="form-control" accept="application/pdf,image/*,.doc,.docx">
                        </div>
                    </div>
                    <div class="mt-3 text-end"><button type="submit" class="btn btn-petcare"><i class="fa-solid fa-floppy-disk me-2"></i>Salvar resultado</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-paperclip me-2"></i>Anexos e observações</h2></div>
            <div class="card-body p-3 p-md-4">
                <?php if ($anexos === []): ?>
                    <p class="text-muted small mb-0">Nenhum anexo enviado.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($anexos as $anexo): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2 px-0">
                                <div class="small"><i class="fa-solid fa-file me-1"></i><?= e($anexo['nome_original'] ?? '') ?></div>
                                <a href="<?= e(url('anexos.download', ['id' => $anexo['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Baixar"><i class="fa-solid fa-download"></i></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($exame['observacoes'])): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="small fw-semibold text-muted">Observações</div>
                        <div class="small" style="white-space: pre-line"><?= e($exame['observacoes']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<template id="resultado-item-template">
    <div class="row g-2 mb-2 resultado-item">
        <div class="col-md-4"><input type="text" name="resultado_descricao[]" class="form-control form-control-sm" placeholder="Descrição"></div>
        <div class="col-md-2"><input type="text" name="resultado_valor[]" class="form-control form-control-sm" placeholder="Valor"></div>
        <div class="col-md-2"><input type="text" name="resultado_unidade[]" class="form-control form-control-sm" placeholder="Unidade"></div>
        <div class="col-md-3"><input type="text" name="resultado_referencia[]" class="form-control form-control-sm" placeholder="Referência"></div>
        <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.resultado-item').remove()"><i class="fa-solid fa-trash"></i></button></div>
    </div>
</template>

<script>
document.getElementById('add-resultado').addEventListener('click', function () {
    var template = document.getElementById('resultado-item-template');
    document.getElementById('resultados-itens').insertAdjacentHTML('beforeend', template.innerHTML);
});
</script>
