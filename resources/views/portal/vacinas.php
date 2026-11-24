<?php
$title = $title ?? 'Vacinas';
$rows = $rows ?? [];
$animais = $animais ?? [];
$animalId = (int) ($animalId ?? 0);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Histórico de vacinação dos seus animais.</p>
    </div>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3">
        <form method="get" action="<?= e(url('portal.vacinas')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="r" value="portal.vacinas">
            <div class="col-md-4">
                <label for="animal_id" class="form-label fw-semibold">Animal</label>
                <select name="animal_id" id="animal_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">Todos</option>
                    <?php foreach ($animais as $a): ?>
                        <option value="<?= e($a['id']) ?>" <?= $animalId === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Data</th><th>Animal</th><th>Vacina</th><th>Próxima dose</th><th>Veterinário</th></tr></thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Nenhuma vacinação encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e(date_br((string) ($row['data_aplicacao'] ?? ''))) ?></td>
                            <td><?= e($row['animal'] ?? '') ?></td>
                            <td><?= e($row['vacina'] ?? '') ?></td>
                            <td><?= e(date_br($row['proxima_dose'] ?? null)) ?></td>
                            <td><?= e($row['veterinario'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
