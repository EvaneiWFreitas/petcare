<?php

$conta = $conta ?? [];
$config = $config ?? [];
$tipo = $tipo ?? 'receber';
$saldo = (float) ($saldo ?? 0);
$values = $values ?? [];
$errors = $errors ?? [];
$formasPagamento = $formasPagamento ?? [];
$title = $title ?? 'Registrar pagamento';
$dataHora = str_replace(' ', 'T', substr((string) ($values['data_pagamento'] ?? date('Y-m-d\TH:i')), 0, 16));
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Informe os dados do pagamento desta conta.</p>
    </div>
    <a href="<?= e(url($config['route'] ?? 'financeiro.index')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-circle-info me-2"></i><?= e($config['label'] ?? 'Conta') ?></h2></div>
            <div class="card-body p-3 p-md-4">
                <dl class="row mb-0">
                    <dt class="col-6 small text-muted">Código</dt>
                    <dd class="col-6 small text-end"><?= e($conta['codigo'] ?? '—') ?></dd>
                    <dt class="col-6 small text-muted">Descrição</dt>
                    <dd class="col-6 small text-end"><?= e($conta['descricao'] ?? '—') ?></dd>
                    <dt class="col-6 small text-muted">Valor</dt>
                    <dd class="col-6 small text-end"><?= e(money($conta['valor'] ?? 0)) ?></dd>
                    <?php if ($tipo === 'receber'): ?>
                        <dt class="col-6 small text-muted">Desconto</dt>
                        <dd class="col-6 small text-end"><?= e(money($conta['desconto'] ?? 0)) ?></dd>
                    <?php endif; ?>
                    <dt class="col-6 small text-muted">Já pago</dt>
                    <dd class="col-6 small text-end"><?= e(money($conta['valor_pago'] ?? 0)) ?></dd>
                    <dt class="col-6 small text-muted">Vencimento</dt>
                    <dd class="col-6 small text-end"><?= e(date_br((string) ($conta['vencimento'] ?? ''))) ?></dd>
                    <dt class="col-6 small text-muted">Status</dt>
                    <dd class="col-6 small text-end"><?= status_badge($conta['status'] ?? null) ?></dd>
                    <dt class="col-6 small fw-bold text-petcare">Saldo em aberto</dt>
                    <dd class="col-6 small text-end fw-bold text-petcare"><?= e(money($saldo)) ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Dados do pagamento</h2></div>
            <div class="card-body p-3 p-md-4">
                <form method="post" action="<?= e(url('financeiro.pay')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
                    <input type="hidden" name="id" value="<?= e($conta['id'] ?? 0) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="field_valor" class="form-label small fw-semibold">Valor a pagar (R$) <span class="text-danger">*</span></label>
                            <input type="number" min="0.01" step="0.01" max="<?= e(number_format(max(0, $saldo), 2, '.', '')) ?>" name="valor" id="field_valor" class="form-control <?= isset($errors['valor']) ? 'is-invalid' : '' ?>" value="<?= e($values['valor'] ?? number_format(max(0, $saldo), 2, '.', '')) ?>" required>
                            <?php if (isset($errors['valor'])): ?><div class="invalid-feedback d-block small"><?= e($errors['valor']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="field_forma_pagamento" class="form-label small fw-semibold">Forma de pagamento <span class="text-danger">*</span></label>
                            <select name="forma_pagamento" id="field_forma_pagamento" class="form-select <?= isset($errors['forma_pagamento']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($formasPagamento as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= selected($values['forma_pagamento'] ?? '', $value) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['forma_pagamento'])): ?><div class="invalid-feedback d-block small"><?= e($errors['forma_pagamento']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="field_data_pagamento" class="form-label small fw-semibold">Data e hora <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="data_pagamento" id="field_data_pagamento" class="form-control <?= isset($errors['data_pagamento']) ? 'is-invalid' : '' ?>" value="<?= e($dataHora) ?>" required>
                            <?php if (isset($errors['data_pagamento'])): ?><div class="invalid-feedback d-block small"><?= e($errors['data_pagamento']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="field_observacoes" class="form-label small fw-semibold">Observações</label>
                            <input type="text" name="observacoes" id="field_observacoes" class="form-control" maxlength="500" value="<?= e($values['observacoes'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= e(url($config['route'] ?? 'financeiro.index')) ?>" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-check me-2"></i>Confirmar pagamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
