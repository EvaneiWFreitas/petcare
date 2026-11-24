<?php

$title = $title ?? 'Nova receita';
$values = $values ?? [];
$errors = $errors ?? [];
$options = $options ?? ['animais' => [], 'clientes' => [], 'veterinarios' => []];
$action = $action ?? 'receitas.store';
$cancelRoute = $cancelRoute ?? 'receitas.index';
$submitLabel = $submitLabel ?? 'Emitir receita';
$medicamentos = $values['medicamentos'] ?? [];
if (!is_array($medicamentos) || $medicamentos === []) {
    $medicamentos = [[]];
}
$itemFields = [
    'medicamento' => 'Medicamento',
    'dosagem' => 'Dosagem',
    'via_administracao' => 'Via',
    'frequencia' => 'Frequência',
    'duracao' => 'Duração',
    'observacoes' => 'Observações',
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Preencha os dados da prescrição e adicione os medicamentos.</p>
    </div>
    <a href="<?= e(url($cancelRoute)) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
</div>

<form method="post" action="<?= e(url($action)) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card border-0 shadow-petcare mb-4">
        <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-file-prescription me-2"></i>Dados da receita</h2></div>
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="field_animal_id" class="form-label small fw-semibold">Animal <span class="text-danger">*</span></label>
                    <select name="animal_id" id="field_animal_id" class="form-select <?= isset($errors['animal_id']) ? 'is-invalid' : '' ?>">
                        <option value="">Selecione...</option>
                        <?php foreach ($options['animais'] ?? [] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected($values['animal_id'] ?? '', $value) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['animal_id'])): ?><div class="invalid-feedback d-block small"><?= e($errors['animal_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="field_cliente_id" class="form-label small fw-semibold">Tutor <span class="text-danger">*</span></label>
                    <select name="cliente_id" id="field_cliente_id" class="form-select <?= isset($errors['cliente_id']) ? 'is-invalid' : '' ?>">
                        <option value="">Selecione...</option>
                        <?php foreach ($options['clientes'] ?? [] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected($values['cliente_id'] ?? '', $value) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['cliente_id'])): ?><div class="invalid-feedback d-block small"><?= e($errors['cliente_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="field_veterinario_id" class="form-label small fw-semibold">Veterinário <span class="text-danger">*</span></label>
                    <select name="veterinario_id" id="field_veterinario_id" class="form-select <?= isset($errors['veterinario_id']) || isset($errors['controle_especial']) ? 'is-invalid' : '' ?>">
                        <option value="">Selecione...</option>
                        <?php foreach ($options['veterinarios'] ?? [] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected($values['veterinario_id'] ?? '', $value) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['veterinario_id'])): ?><div class="invalid-feedback d-block small"><?= e($errors['veterinario_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="field_data_prescricao" class="form-label small fw-semibold">Data da prescrição <span class="text-danger">*</span></label>
                    <input type="date" name="data_prescricao" id="field_data_prescricao" class="form-control <?= isset($errors['data_prescricao']) ? 'is-invalid' : '' ?>" value="<?= e($values['data_prescricao'] ?? '') ?>">
                    <?php if (isset($errors['data_prescricao'])): ?><div class="invalid-feedback d-block small"><?= e($errors['data_prescricao']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-12">
                    <label for="field_orientacoes" class="form-label small fw-semibold">Orientações</label>
                    <textarea name="orientacoes" id="field_orientacoes" rows="3" class="form-control <?= isset($errors['orientacoes']) ? 'is-invalid' : '' ?>"><?= e($values['orientacoes'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="controle_especial" value="0">
                    <div class="form-check form-switch mt-3">
                        <input type="checkbox" name="controle_especial" value="1" id="field_controle_especial" class="form-check-input" <?= checked($values['controle_especial'] ?? 0, 1) ?>>
                        <label class="form-check-label small fw-semibold" for="field_controle_especial">Controle especial</label>
                    </div>
                    <small class="text-muted d-block mt-1">Medicamentos sujeitos a controle especial exigem CRMV e assinatura</small>
                    <?php if (isset($errors['controle_especial'])): ?><div class="invalid-feedback d-block small"><?= e($errors['controle_especial']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="field_assinatura" class="form-label small fw-semibold">Assinatura (opcional)</label>
                    <input type="text" name="assinatura" id="field_assinatura" class="form-control" value="<?= e($values['assinatura'] ?? '') ?>" placeholder="Nome do responsável pela assinatura">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-petcare mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-pills me-2"></i>Medicamentos</h2>
            <button type="button" id="add-medicamento" class="btn btn-outline-petcare btn-sm"><i class="fa-solid fa-plus me-1"></i>Adicionar medicamento</button>
        </div>
        <div class="card-body p-0">
            <?php if (isset($errors['medicamentos'])): ?><div class="alert alert-danger m-3 small"><?= e($errors['medicamentos']) ?></div><?php endif; ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($itemFields as $label): ?><th class="small text-uppercase text-muted fw-bold"><?= e($label) ?></th><?php endforeach; ?>
                            <th class="small text-uppercase text-muted fw-bold text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="medicamentos-body">
                        <?php foreach ($medicamentos as $index => $item): ?>
                            <tr>
                                <?php foreach ($itemFields as $name => $label): ?>
                                    <td><input type="text" class="form-control form-control-sm" name="medicamentos[<?= (int) $index ?>][<?= e($name) ?>]" value="<?= e($item[$name] ?? '') ?>" placeholder="<?= e($label) ?>"></td>
                                <?php endforeach; ?>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-petcare">
        <div class="card-footer bg-white border-0 d-flex justify-content-end gap-2 p-3 p-md-4">
            <a href="<?= e(url($cancelRoute)) ?>" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-floppy-disk me-2"></i><?= e($submitLabel) ?></button>
        </div>
    </div>
</form>

<template id="medicamento-template">
    <tr>
        <?php foreach ($itemFields as $name => $label): ?>
            <td><input type="text" class="form-control form-control-sm" name="medicamentos[__INDEX__][<?= e($name) ?>]" value="" placeholder="<?= e($label) ?>"></td>
        <?php endforeach; ?>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
    </tr>
</template>

<script>
(function () {
    var body = document.getElementById('medicamentos-body');
    var template = document.getElementById('medicamento-template');
    var button = document.getElementById('add-medicamento');
    var index = <?= (int) count($medicamentos) ?>;
    button.addEventListener('click', function () {
        var html = template.innerHTML.replace(/__INDEX__/g, index);
        body.insertAdjacentHTML('beforeend', html);
        index++;
    });
})();
</script>
