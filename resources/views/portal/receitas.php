<?php
$title = $title ?? 'Receitas';
$rows = $rows ?? [];
?>
<div class="mb-4">
    <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
    <p class="text-muted mb-0 small">Receitas emitidas para seus animais.</p>
</div>

<?php if ($rows === []): ?>
    <div class="card border-0 shadow-petcare"><div class="card-body text-center py-5 text-muted"><i class="fa-solid fa-prescription-bottle fa-2x d-block mb-2"></i>Nenhuma receita encontrada.</div></div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($rows as $row): ?>
            <div class="col-12">
                <div class="card border-0 shadow-petcare">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                            <div>
                                <h2 class="h5 mb-1">Receita <?= e($row['codigo'] ?? '') ?></h2>
                                <div class="text-muted small"><?= e($row['animal'] ?? '') ?> · <?= e(date_br((string) ($row['data_prescricao'] ?? ''))) ?> · Dr(a). <?= e($row['veterinario'] ?? '') ?></div>
                            </div>
                            <?php if ((int) ($row['controle_especial'] ?? 0) === 1): ?><span class="badge bg-warning text-dark align-self-start">Controle especial</span><?php endif; ?>
                        </div>
                        <?php if (!empty($row['orientacoes'])): ?><p class="mb-3"><?= nl2br(e($row['orientacoes'])) ?></p><?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Medicamento</th><th>Dosagem</th><th>Via</th><th>Frequência</th><th>Duração</th></tr></thead>
                                <tbody>
                                    <?php foreach (($row['itens'] ?? []) as $item): ?>
                                        <tr>
                                            <td><?= e($item['medicamento'] ?? '') ?></td>
                                            <td><?= e($item['dosagem'] ?? '—') ?></td>
                                            <td><?= e($item['via_administracao'] ?? '—') ?></td>
                                            <td><?= e($item['frequencia'] ?? '—') ?></td>
                                            <td><?= e($item['duracao'] ?? '—') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($row['itens'])): ?>
                                        <tr><td colspan="5" class="text-muted">Sem itens informados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
