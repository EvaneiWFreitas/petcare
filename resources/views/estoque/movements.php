<?php

$produtos = $produtos ?? [];
$produtoId = (int) ($produtoId ?? 0);
$tipo = $tipo ?? 'entrada';
$de = $de ?? '';
$ate = $ate ?? '';

$title = $title ?? 'Movimentações de estoque';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'estoque';
$createRoute = null;
$search = $search ?? '';
$extraFilters = $extraFilters ?? '';
$paginator = $paginator ?? null;
$actions = [];

ob_start();
require __DIR__ . '/../common/list.php';
$listHtml = (string) ob_get_clean();
$listHtml = str_replace('estoque.index', 'estoque.movements', $listHtml);
$filters = http_build_query(array_filter([
    'produto_id' => $produtoId,
    'tipo' => $tipo,
    'de' => $de,
    'ate' => $ate,
]));
if ($filters !== '') {
    $listHtml = str_replace('&amp;page=', '&amp;' . htmlspecialchars($filters, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '&amp;page=', $listHtml);
}
?>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Registrar movimentação</h2>
    </div>
    <div class="card-body p-3 p-md-4">
        <form method="post" action="<?= e(url('estoque.move')) ?>" class="row g-3 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-4">
                <label for="move_produto_id" class="form-label small fw-semibold">Produto</label>
                <select name="produto_id" id="move_produto_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?= e($produto['id']) ?>" <?= selected($produtoId, (int) $produto['id']) ?>><?= e($produto['nome'] . ' · ' . $produto['codigo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="move_tipo" class="form-label small fw-semibold">Tipo</label>
                <select name="tipo" id="move_tipo" class="form-select" required>
                    <option value="entrada" <?= selected($tipo, 'entrada') ?>>Entrada</option>
                    <option value="saida" <?= selected($tipo, 'saida') ?>>Saída</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="move_quantidade" class="form-label small fw-semibold">Quantidade</label>
                <input type="number" min="1" step="1" name="quantidade" id="move_quantidade" class="form-control" value="1" required>
            </div>
            <div class="col-md-2">
                <label for="move_lote" class="form-label small fw-semibold">Lote</label>
                <input type="text" name="lote" id="move_lote" class="form-control" maxlength="80">
            </div>
            <div class="col-md-2">
                <label for="move_documento" class="form-label small fw-semibold">Documento</label>
                <input type="text" name="documento" id="move_documento" class="form-control" maxlength="120">
            </div>
            <div class="col-md-8">
                <label for="move_observacoes" class="form-label small fw-semibold">Observações</label>
                <input type="text" name="observacoes" id="move_observacoes" class="form-control" maxlength="255">
            </div>
            <div class="col-md-4 d-flex justify-content-md-end">
                <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-check me-2"></i>Registrar</button>
            </div>
        </form>
    </div>
</div>

<?= $listHtml ?>
