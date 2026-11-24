<?php
$title = $title ?? 'Relatório';
$columns = $columns ?? [];
$rows = $rows ?? [];
$periodo = $periodo ?? ['inicio' => '', 'fim' => ''];
$tipo = $tipo ?? '';
$totais = $totais ?? [];
$valueColumn = $valueColumn ?? null;
$formato = $formato ?? 'tela';
$veterinario_id = $veterinario_id ?? 0;
$params = array_filter([
    'tipo' => $tipo,
    'inicio' => $periodo['inicio'] ?? '',
    'fim' => $periodo['fim'] ?? '',
    'veterinario_id' => $veterinario_id ?: null,
]);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Período: <?= e(date_br($periodo['inicio'] ?? null)) ?> a <?= e(date_br($periodo['fim'] ?? null)) ?></p>
    </div>
    <?php if ($formato !== 'impressao'): ?>
        <div class="d-flex gap-2 no-print">
            <a href="<?= e(url('relatorios.index')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
            <a href="<?= e(url('relatorios.generate', $params + ['formato' => 'csv'])) ?>" class="btn btn-outline-petcare"><i class="fa-solid fa-file-csv me-2"></i>Exportar CSV</a>
            <a href="<?= e(url('relatorios.generate', $params + ['formato' => 'impressao'])) ?>" target="_blank" class="btn btn-petcare"><i class="fa-solid fa-print me-2"></i>Imprimir</a>
        </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <?php foreach ($columns as $label => $key): ?>
                        <th class="small text-uppercase text-muted fw-bold"><?= e($label) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= max(1, count($columns)) ?>" class="text-center py-5 text-muted">Nenhum registro encontrado para o período.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($columns as $label => $key): ?>
                                <?php
                                $value = $row[$key] ?? '';
                                if ($valueColumn === $key && is_numeric($value)) {
                                    $display = money($value);
                                } elseif ($key === 'status') {
                                    $display = status_badge((string) $value);
                                } else {
                                    $display = e($value === '' || $value === null ? '—' : $value);
                                }
                                ?>
                                <td><?= $display ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if ($totais !== [] && $valueColumn !== null): ?>
                <tfoot>
                    <tr class="fw-bold">
                        <?php $first = true; foreach ($columns as $label => $key): ?>
                            <?php if ($first): $first = false; ?>
                                <td>Total</td>
                            <?php elseif ($key === $valueColumn): ?>
                                <td><?= e(money($totais[$valueColumn] ?? 0)) ?></td>
                            <?php else: ?>
                                <td></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
