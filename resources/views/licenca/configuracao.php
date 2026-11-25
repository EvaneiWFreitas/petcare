<?php include __DIR__ . '/_nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare">Configuração do prazo de funcionamento</h1>
        <p class="text-muted mb-0 small">Defina o prazo inicial, a data do bloqueio e os dias em que o aviso aparecerá.</p>
    </div>
</div>

<form method="post" action="<?= e(url('licenca.salvar-configuracao')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card border-0 shadow-petcare">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="dias_iniciais" class="form-label small fw-semibold">Prazo inicial da licença (dias) <span class="text-danger">*</span></label>
                    <input type="number" min="1" max="3650" name="dias_iniciais" id="dias_iniciais" class="form-control" value="<?= (int) ($settings['dias_iniciais'] ?? 30) ?>" required>
                    <small class="text-muted d-block mt-1">Usado quando uma nova licença é ativada.</small>
                </div>
                <div class="col-md-6">
                    <label for="avisos" class="form-label small fw-semibold">Dias de aviso</label>
                    <input type="text" name="avisos" id="avisos" class="form-control" value="<?= e($settings['avisos_raw'] ?? '') ?>" placeholder="10,5,3,1,0">
                    <small class="text-muted d-block mt-1">Separados por vírgula. Use 0 para avisar no dia do vencimento.</small>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 d-flex justify-content-end gap-2 p-3 p-md-4">
            <a href="<?= e(url('licenca.index')) ?>" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-floppy-disk me-2"></i>Salvar configuração</button>
        </div>
    </div>
</form>

<form method="post" action="<?= e(url('licenca.salvar-data-bloqueio')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card border-0 shadow-petcare">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="data_bloqueio" class="form-label small fw-semibold">Data do bloqueio <span class="text-danger">*</span></label>
                    <input type="date" name="data_bloqueio" id="data_bloqueio" class="form-control" value="<?= e($block_date ?? '') ?>" min="<?= date('Y-m-d') ?>" required>
                    <small class="text-muted d-block mt-1">O PetCare será bloqueado a partir desta data. O vencimento interno será ajustado para o dia anterior.</small>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info border-0 small mb-0" role="alert">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        <?php if (!empty($block_date)): ?>
                            Bloqueio programado para <strong><?= e(date_br((string) $block_date)) ?></strong>.
                        <?php else: ?>
                            Nenhuma data programada. Ative a licença ou defina uma data acima.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 d-flex justify-content-end gap-2 p-3 p-md-4">
            <button type="submit" class="btn btn-outline-petcare"><i class="fa-solid fa-calendar-lock me-2"></i>Salvar data do bloqueio</button>
        </div>
    </div>
</form>
