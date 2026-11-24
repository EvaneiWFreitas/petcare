<?php
$title = $title ?? 'Exames';
$rows = $rows ?? [];
?>
<div class="mb-4">
    <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
    <p class="text-muted mb-0 small">Exames liberados para visualização.</p>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Código</th><th>Animal</th><th>Tipo</th><th>Solicitação</th><th>Resultado</th><th>Status</th></tr></thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Nenhum exame liberado encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['codigo'] ?? '') ?></td>
                            <td><?= e($row['animal'] ?? '') ?></td>
                            <td><?= e($row['tipo'] ?? '') ?></td>
                            <td><?= e(date_br($row['data_solicitacao'] ?? null)) ?></td>
                            <td><?= e(date_br($row['data_resultado'] ?? null)) ?></td>
                            <td><?= status_badge((string) ($row['status'] ?? '')) ?></td>
                        </tr>
                        <?php if (!empty($row['resultados']) || !empty($row['observacoes'])): ?>
                            <tr>
                                <td colspan="6" class="bg-light">
                                    <?php if (!empty($row['resultados'])): ?><div><strong>Resultados:</strong> <?= nl2br(e($row['resultados'])) ?></div><?php endif; ?>
                                    <?php if (!empty($row['observacoes'])): ?><div class="mt-1"><strong>Observações:</strong> <?= nl2br(e($row['observacoes'])) ?></div><?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
