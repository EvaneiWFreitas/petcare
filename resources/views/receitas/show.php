<?php

$receita = $receita ?? [];
$itens = $itens ?? [];
$title = $title ?? 'Detalhes da receita';
$sections = $sections ?? [];
$actions = $actions ?? [];
$recordId = $recordId ?? (int) ($receita['id'] ?? 0);

ob_start();
require __DIR__ . '/../common/detail.php';
$html = (string) ob_get_clean();
?>

<?= $html ?>

<div class="card border-0 shadow-petcare mt-4">
    <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-pills me-2"></i>Medicamentos prescritos</h2>
        <a href="<?= e(url('receitas.print', ['id' => $recordId])) ?>" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-print me-1"></i>Imprimir receita</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Medicamento</th>
                    <th class="small text-uppercase text-muted fw-bold">Dosagem</th>
                    <th class="small text-uppercase text-muted fw-bold">Via</th>
                    <th class="small text-uppercase text-muted fw-bold">Frequência</th>
                    <th class="small text-uppercase text-muted fw-bold">Duração</th>
                    <th class="small text-uppercase text-muted fw-bold">Observações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($itens === []): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum medicamento registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td class="small fw-semibold"><?= e($item['medicamento'] ?? '') ?></td>
                            <td class="small"><?= e($item['dosagem'] ?? '') ?></td>
                            <td class="small"><?= e($item['via_administracao'] ?? '') ?></td>
                            <td class="small"><?= e($item['frequencia'] ?? '') ?></td>
                            <td class="small"><?= e($item['duracao'] ?? '') ?></td>
                            <td class="small"><?= e($item['observacoes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
