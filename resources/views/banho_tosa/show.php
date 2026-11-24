<?php

$servico = $servico ?? [];
$conta = $conta ?? null;
$sections = $sections ?? [];
$actions = $actions ?? [];
$recordId = (int) ($recordId ?? ($servico['id'] ?? 0));

ob_start();
require __DIR__ . '/../common/detail.php';
$detailHtml = (string) ob_get_clean();
?>
<?= $detailHtml ?>

<?php if (($servico['status'] ?? '') === 'agendado'): ?>
    <div class="alert alert-info border-0 shadow-petcare mt-4 mb-0">
        <i class="fa-solid fa-circle-info me-2"></i>Serviço agendado. Para registrar uma nova sessão, utilize o botão <strong>Novo agendamento</strong> acima.
    </div>
<?php endif; ?>

<?php if ($conta !== null): ?>
    <div class="card border-0 shadow-petcare mt-4">
        <div class="card-header bg-white border-0 py-3"><h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-receipt me-2"></i>Conta a receber gerada</h2></div>
        <div class="card-body p-3 p-md-4">
            <dl class="row mb-0">
                <dt class="col-sm-3 small text-muted">Código</dt>
                <dd class="col-sm-9 small"><?= e($conta['codigo'] ?? '—') ?></dd>
                <dt class="col-sm-3 small text-muted">Valor</dt>
                <dd class="col-sm-9 small"><?= e(money($conta['valor'] ?? 0)) ?></dd>
                <dt class="col-sm-3 small text-muted">Status</dt>
                <dd class="col-sm-9 small"><?= status_badge($conta['status'] ?? null) ?></dd>
            </dl>
        </div>
    </div>
<?php endif; ?>
