<?php
$title = $title ?? 'Solicitar agendamento';
$animais = $animais ?? [];
$veterinarios = $veterinarios ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$tipos = ['Consulta', 'Retorno', 'Vacinação', 'Exame', 'Cirurgia', 'Banho e tosa'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Preencha os dados para solicitar um agendamento.</p>
    </div>
    <a href="<?= e(url('portal.agendamentos')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
</div>

<div class="card border-0 shadow-petcare">
    <div class="card-body p-4">
        <form method="post" action="<?= e(url('portal.store-agendamento')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="animal_id" class="form-label fw-semibold">Animal</label>
                    <select class="form-select <?= isset($errors['animal_id']) ? 'is-invalid' : '' ?>" id="animal_id" name="animal_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($animais as $a): ?>
                            <option value="<?= e($a['id']) ?>" <?= selected($values['animal_id'] ?? '', $a['id']) ?>><?= e($a['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['animal_id'])): ?><div class="invalid-feedback"><?= e($errors['animal_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="veterinario_id" class="form-label fw-semibold">Veterinário</label>
                    <select class="form-select <?= isset($errors['veterinario_id']) ? 'is-invalid' : '' ?>" id="veterinario_id" name="veterinario_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($veterinarios as $v): ?>
                            <option value="<?= e($v['id']) ?>" <?= selected($values['veterinario_id'] ?? '', $v['id']) ?>><?= e($v['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['veterinario_id'])): ?><div class="invalid-feedback"><?= e($errors['veterinario_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="tipo" class="form-label fw-semibold">Tipo</label>
                    <select class="form-select <?= isset($errors['tipo']) ? 'is-invalid' : '' ?>" id="tipo" name="tipo" required>
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?= e($tipo) ?>" <?= selected($values['tipo'] ?? '', $tipo) ?>><?= e($tipo) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['tipo'])): ?><div class="invalid-feedback"><?= e($errors['tipo']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="data_agendamento" class="form-label fw-semibold">Data</label>
                    <input type="date" class="form-control <?= isset($errors['data_agendamento']) ? 'is-invalid' : '' ?>" id="data_agendamento" name="data_agendamento" value="<?= e($values['data_agendamento'] ?? '') ?>" required>
                    <?php if (isset($errors['data_agendamento'])): ?><div class="invalid-feedback"><?= e($errors['data_agendamento']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label for="hora_inicio" class="form-label fw-semibold">Hora</label>
                    <input type="time" class="form-control <?= isset($errors['hora_inicio']) ? 'is-invalid' : '' ?>" id="hora_inicio" name="hora_inicio" value="<?= e(substr((string) ($values['hora_inicio'] ?? ''), 0, 5)) ?>" required>
                    <?php if (isset($errors['hora_inicio'])): ?><div class="invalid-feedback"><?= e($errors['hora_inicio']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label for="duracao_minutos" class="form-label fw-semibold">Duração (min)</label>
                    <input type="number" min="10" step="5" class="form-control <?= isset($errors['duracao_minutos']) ? 'is-invalid' : '' ?>" id="duracao_minutos" name="duracao_minutos" value="<?= e($values['duracao_minutos'] ?? 30) ?>" required>
                    <?php if (isset($errors['duracao_minutos'])): ?><div class="invalid-feedback"><?= e($errors['duracao_minutos']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label for="observacoes" class="form-label fw-semibold">Observações</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= e($values['observacoes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= e(url('portal.agendamentos')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-paper-plane me-2"></i>Enviar solicitação</button>
            </div>
        </form>
    </div>
</div>
