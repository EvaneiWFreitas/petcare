<?php
$title = $title ?? 'Pagamentos';
$contas = $contas ?? [];
$pagamentos = $pagamentos ?? [];
?>
<div class="mb-4">
    <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
    <p class="text-muted mb-0 small">Contas e pagamentos vinculados ao seu cadastro.</p>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Contas a receber</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Código</th><th>Descrição</th><th>Vencimento</th><th>Valor</th><th>Pago</th><th>Saldo</th><th>Status</th></tr></thead>
            <tbody>
                <?php if ($contas === []): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Nenhuma conta encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($contas as $c): ?>
                        <?php $saldo = (float) ($c['valor'] ?? 0) - (float) ($c['valor_pago'] ?? 0) - (float) ($c['desconto'] ?? 0); ?>
                        <tr>
                            <td><?= e($c['codigo'] ?? '') ?></td>
                            <td><?= e($c['descricao'] ?? '') ?></td>
                            <td><?= e(date_br($c['vencimento'] ?? null)) ?></td>
                            <td><?= e(money($c['valor'] ?? 0)) ?></td>
                            <td><?= e(money($c['valor_pago'] ?? 0)) ?></td>
                            <td><?= e(money(max(0, $saldo))) ?></td>
                            <td><?= status_badge((string) ($c['status'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Pagamentos registrados</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Data</th><th>Conta</th><th>Descrição</th><th>Forma</th><th>Valor</th></tr></thead>
            <tbody>
                <?php if ($pagamentos === []): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum pagamento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagamentos as $p): ?>
                        <tr>
                            <td><?= e(date_br($p['data_pagamento'] ?? null, true)) ?></td>
                            <td><?= e($p['conta_codigo'] ?? '—') ?></td>
                            <td><?= e($p['descricao'] ?? '—') ?></td>
                            <td><?= e($p['forma_pagamento'] ?? '—') ?></td>
                            <td><?= e(money($p['valor'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
