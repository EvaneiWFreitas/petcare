<?php

$values = $values ?? [];
$errors = $errors ?? [];
$produtos = $produtos ?? [];
$fornecedores = $fornecedores ?? [];
$formasPagamento = $formasPagamento ?? [];
$action = $action ?? 'compras.store';
$title = $title ?? 'Nova compra';

$itens = $values['itens'] ?? [];
if (!is_array($itens) || $itens === []) {
    $itens = [['produto_id' => '', 'quantidade' => '1', 'valor_unitario' => '0']];
}
$errorFor = static function (string $key) use ($errors): string {
    $message = (string) ($errors[$key] ?? '');
    return $message !== '' ? '<div class="invalid-feedback d-block small">' . e($message) . '</div>' : '';
};
$optionsHtml = static function () use ($produtos): string {
    $html = '<option value="">Selecione...</option>';
    foreach ($produtos as $value => $label) {
        $html .= '<option value="' . e($value) . '">' . e($label) . '</option>';
    }
    return $html;
};
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Informe o fornecedor e os produtos adquiridos.</p>
    </div>
    <a href="<?= e(url('compras.index')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
</div>

<form method="post" action="<?= e(url($action)) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card border-0 shadow-petcare mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="field_codigo" class="form-label small fw-semibold">Código</label>
                    <input type="text" name="codigo" id="field_codigo" class="form-control" value="<?= e($values['codigo'] ?? '') ?>" readonly>
                    <?= $errorFor('codigo') ?>
                </div>
                <div class="col-md-5">
                    <label for="field_fornecedor_id" class="form-label small fw-semibold">Fornecedor <span class="text-danger">*</span></label>
                    <select name="fornecedor_id" id="field_fornecedor_id" class="form-select <?= isset($errors['fornecedor_id']) ? 'is-invalid' : '' ?>">
                        <option value="">Selecione...</option>
                        <?php foreach ($fornecedores as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected($values['fornecedor_id'] ?? '', $value) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $errorFor('fornecedor_id') ?>
                </div>
                <div class="col-md-3">
                    <label for="field_data_compra" class="form-label small fw-semibold">Data da compra <span class="text-danger">*</span></label>
                    <input type="date" name="data_compra" id="field_data_compra" class="form-control <?= isset($errors['data_compra']) ? 'is-invalid' : '' ?>" value="<?= e($values['data_compra'] ?? date('Y-m-d')) ?>">
                    <?= $errorFor('data_compra') ?>
                </div>
                <div class="col-md-4">
                    <label for="field_forma_pagamento" class="form-label small fw-semibold">Forma de pagamento</label>
                    <select name="forma_pagamento" id="field_forma_pagamento" class="form-select">
                        <option value="">Selecione...</option>
                        <?php foreach ($formasPagamento as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected($values['forma_pagamento'] ?? '', $value) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-petcare mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-list-check me-2"></i>Itens da compra</h2>
            <button type="button" class="btn btn-sm btn-outline-petcare" id="addItem"><i class="fa-solid fa-plus me-1"></i>Adicionar item</button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase text-muted">Produto</th>
                        <th class="small text-uppercase text-muted" style="width: 150px">Quantidade</th>
                        <th class="small text-uppercase text-muted" style="width: 180px">Valor unitário</th>
                        <th class="small text-uppercase text-muted text-end" style="width: 150px">Subtotal</th>
                        <th class="small text-uppercase text-muted text-end" style="width: 60px"></th>
                    </tr>
                </thead>
                <tbody id="itensBody" data-next="<?= e(count($itens)) ?>">
                    <?php foreach ($itens as $index => $item): ?>
                        <?php
                        if (!is_array($item)) {
                            $item = [];
                        }
                        $produtoId = (string) ($item['produto_id'] ?? '');
                        $quantidade = (string) ($item['quantidade'] ?? '1');
                        $valorUnitario = (string) ($item['valor_unitario'] ?? '0');
                        ?>
                        <tr>
                            <td>
                                <select name="itens[<?= e($index) ?>][produto_id]" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($produtos as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= selected($produtoId, $value) ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $errorFor("itens.{$index}.produto_id") ?>
                            </td>
                            <td><input type="number" min="1" step="1" name="itens[<?= e($index) ?>][quantidade]" class="form-control item-quantidade" value="<?= e($quantidade) ?>" required><?= $errorFor("itens.{$index}.quantidade") ?></td>
                            <td><input type="number" min="0" step="0.01" name="itens[<?= e($index) ?>][valor_unitario]" class="form-control item-valor" value="<?= e($valorUnitario) ?>" required><?= $errorFor("itens.{$index}.valor_unitario") ?></td>
                            <td class="text-end item-subtotal fw-semibold"><?= e(money((int) $quantidade * (float) $valorUnitario)) ?></td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remover"><i class="fa-solid fa-trash"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="text-end small text-uppercase text-muted">Total</th>
                        <th class="text-end" id="totalCompra"><?= e(money(0)) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?= $errorFor('itens') ?>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= e(url('compras.index')) ?>" class="btn btn-light">Cancelar</a>
        <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-floppy-disk me-2"></i>Registrar compra</button>
    </div>
</form>

<template id="itemTemplate">
    <tr>
        <td>
            <select name="itens[__INDEX__][produto_id]" class="form-select" required>
                <?= $optionsHtml() ?>
            </select>
        </td>
        <td><input type="number" min="1" step="1" name="itens[__INDEX__][quantidade]" class="form-control item-quantidade" value="1" required></td>
        <td><input type="number" min="0" step="0.01" name="itens[__INDEX__][valor_unitario]" class="form-control item-valor" value="0" required></td>
        <td class="text-end item-subtotal fw-semibold">R$ 0,00</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remover"><i class="fa-solid fa-trash"></i></button></td>
    </tr>
</template>

<script>
(function () {
    var body = document.getElementById('itensBody');
    var template = document.getElementById('itemTemplate');
    var total = document.getElementById('totalCompra');
    var next = parseInt(body.dataset.next || '0', 10);
    function format(value) {
        return 'R$ ' + value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    function calculate() {
        var sum = 0;
        body.querySelectorAll('tr').forEach(function (row) {
            var quantity = parseFloat(row.querySelector('.item-quantidade').value) || 0;
            var price = parseFloat(row.querySelector('.item-valor').value) || 0;
            row.querySelector('.item-subtotal').textContent = format(quantity * price);
            sum += quantity * price;
        });
        total.textContent = format(sum);
    }
    document.getElementById('addItem').addEventListener('click', function () {
        body.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(next)));
        next += 1;
        body.dataset.next = String(next);
        calculate();
    });
    body.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-item');
        if (button) {
            button.closest('tr').remove();
            calculate();
        }
    });
    body.addEventListener('input', calculate);
    calculate();
})();
</script>
