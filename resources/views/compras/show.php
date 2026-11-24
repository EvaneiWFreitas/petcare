<?php

$compra = $compra ?? [];
$itens = $itens ?? [];
$sections = $sections ?? [];
$actions = $actions ?? [];
$recordId = (int) ($recordId ?? ($compra['id'] ?? 0));

ob_start();
require __DIR__ . '/../common/detail.php';
$detailHtml = (string) ob_get_clean();
?>
<?= $detailHtml ?>

<div class="card border-0 shadow-petcare mt-4">
    <div class="card-header bg-white border-0 py-3">
        <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-boxes-packing me-2"></i>Itens da compra</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted">Produto</th>
                    <th class="small text-uppercase text-muted">Lote</th>
                    <th class="small text-uppercase text-muted">Quantidade</th>
                    <th class="small text-uppercase text-muted">Valor unitário</th>
                    <th class="small text-uppercase text-muted text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($itens === []): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fa-regular fa-folder-open fa-2x d-block mb-2"></i>Nenhum item registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><div class="fw-semibold small"><?= e($item['produto'] ?? '') ?></div><div class="small text-muted"><?= e($item['codigo'] ?? '') ?></div></td>
                            <td><?= e($item['lote'] ?? '—') ?></td>
                            <td><?= e((int) ($item['quantidade'] ?? 0)) ?></td>
                            <td><?= e(money($item['valor_unitario'] ?? 0)) ?></td>
                            <td class="text-end fw-semibold"><?= e(money($item['valor_total'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($compra['status'] ?? '') === 'pendente'): ?>
    <div class="card border-0 shadow-petcare mt-4">
        <div class="card-body p-3 p-md-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h6 mb-1 fw-bold text-petcare"><i class="fa-solid fa-circle-check me-2"></i>Confirmar recebimento</h2>
                <p class="text-muted small mb-0">O estoque será atualizado e uma conta a pagar será gerada quando aplicável.</p>
            </div>
            <form method="post" action="<?= e(url('compras.receive', ['id' => $recordId])) ?>" onsubmit="return confirm('Confirma o recebimento desta compra?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($recordId) ?>">
                <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-truck-ramp-box me-2"></i>Confirmar recebimento</button>
            </form>
        </div>
    </div>
<?php endif; ?>
