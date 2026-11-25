<?php include __DIR__ . '/_nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare">Histórico de alterações</h1>
        <p class="text-muted mb-0 small">Registro de ativações, prorrogações, reativações e mudanças de modalidade.</p>
    </div>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="get" action="<?= e(url('licenca.historico')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="r" value="licenca.historico">
            <div class="col-md-6">
                <label for="licenca_id" class="form-label small fw-semibold">Licença</label>
                <select name="licenca_id" id="licenca_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($licencas as $row): ?>
                        <option value="<?= (int) $row['id'] ?>" <?= selected($licenca_id, (int) $row['id']) ?>>
                            <?= e($row['clinica_nome'] ?? '—') ?> — <?= e($row['codigo_licenca'] ?? '—') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-petcare"><i class="fa-solid fa-magnifying-glass me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Data</th>
                    <th class="small text-uppercase text-muted fw-bold">Clínica</th>
                    <th class="small text-uppercase text-muted fw-bold">Operação</th>
                    <th class="small text-uppercase text-muted fw-bold">Dias</th>
                    <th class="small text-uppercase text-muted fw-bold">Vencimento anterior</th>
                    <th class="small text-uppercase text-muted fw-bold">Novo vencimento</th>
                    <th class="small text-uppercase text-muted fw-bold">Responsável</th>
                    <th class="small text-uppercase text-muted fw-bold">Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($historico === []): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">Nenhuma alteração registrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($historico as $row): ?>
                        <?php
                        $tipos = [
                            'ativacao_30_dias' => 'Ativação 30 dias',
                            'prorrogacao' => 'Prorrogação',
                            'reativacao' => 'Reativação',
                            'data_bloqueio' => 'Data do bloqueio',
                            'licenca_ilimitada' => 'Licença ilimitada',
                            'desativacao_ilimitada' => 'Desativação ilimitada',
                        ];
                        ?>
                        <tr>
                            <td class="text-nowrap small"><?= e(date_br((string) ($row['created_at'] ?? ''), true)) ?></td>
                            <td><?= e($row['clinica_nome'] ?? '—') ?></td>
                            <td><span class="badge bg-petcare-soft text-petcare"><?= e($tipos[$row['tipo_operacao'] ?? ''] ?? ucfirst(str_replace('_', ' ', (string) ($row['tipo_operacao'] ?? '')))) ?></span></td>
                            <td><?= ($row['dias_adicionados'] ?? null) !== null ? (int) $row['dias_adicionados'] : '—' ?></td>
                            <td><?= e(date_br($row['vencimento_anterior'] ?? null)) ?></td>
                            <td><?= e(date_br($row['novo_vencimento'] ?? null)) ?></td>
                            <td><?= e($row['usuario_nome'] ?? 'Sistema') ?></td>
                            <td class="small text-muted"><?= e($row['descricao'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
