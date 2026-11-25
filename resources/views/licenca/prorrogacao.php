<?php include __DIR__ . '/_nav.php'; ?>

<?php $selecionada = (int) \PetCare\Core\Request::get('clinica_id', 0); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare">Prorrogação de licenças</h1>
        <p class="text-muted mb-0 small">Some dias ao vencimento atual ou reative uma licença vencida.</p>
    </div>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="post" action="<?= e(url('licenca.prorrogar')) ?>" class="row g-3 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-7">
                <label for="clinica_id" class="form-label small fw-semibold">Clínica <span class="text-danger">*</span></label>
                <select name="clinica_id" id="clinica_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($licencas as $row): ?>
                        <option value="<?= (int) $row['id'] ?>" <?= selected($selecionada, (int) $row['id']) ?>>
                            <?= e($row['clinica_nome'] ?? '—') ?> — <?= e($row['codigo_licenca'] ?? '—') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="dias" class="form-label small fw-semibold">Dias <span class="text-danger">*</span></label>
                <input type="number" min="1" max="3650" name="dias" id="dias" class="form-control" value="30" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-petcare w-100"><i class="fa-solid fa-calendar-plus me-2"></i>Prorrogar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Clínica</th>
                    <th class="small text-uppercase text-muted fw-bold">Vencimento atual</th>
                    <th class="small text-uppercase text-muted fw-bold">Situação</th>
                    <th class="small text-uppercase text-muted fw-bold">Cálculo</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($licencas === []): ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">Nenhuma licença cadastrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($licencas as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($row['clinica_nome'] ?? '—') ?></div>
                                <div class="small text-muted"><?= e($row['codigo_licenca'] ?? '—') ?></div>
                            </td>
                            <td><?= !empty($row['permanente']) ? 'Sem vencimento' : e(date_br($row['data_vencimento'] ?? null)) ?></td>
                            <td><?= license_badge($row['status_efetivo'] ?? null) ?></td>
                            <td class="small text-muted">
                                <?php if (!empty($row['permanente'])): ?>
                                    Desative a licença ilimitada antes de prorrogar.
                                <?php elseif (!empty($row['bloqueada'])): ?>
                                    A contagem começa na data da reativação.
                                <?php else: ?>
                                    Os dias serão somados ao vencimento atual.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
