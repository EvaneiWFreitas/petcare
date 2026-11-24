<?php
$title = $title ?? 'Relatórios';
$types = $types ?? [];
$veterinarios = $veterinarios ?? [];
$inicio = $inicio ?? date('Y-m-01');
$fim = $fim ?? date('Y-m-t');
$icons = [
    'clientes' => 'users', 'animais' => 'paw', 'atendimentos' => 'stethoscope', 'consultas_veterinario' => 'user-doctor',
    'vacinas_aplicadas' => 'syringe', 'vacinas_pendentes' => 'clock', 'exames' => 'microscope', 'cirurgias' => 'scissors',
    'internacoes' => 'bed-pulse', 'produtos_vendidos' => 'cart-shopping', 'estoque' => 'boxes-stacked', 'produtos_vencidos' => 'triangle-exclamation',
    'contas_pagar' => 'file-invoice-dollar', 'contas_receber' => 'hand-holding-dollar', 'faturamento' => 'chart-line', 'despesas' => 'money-bill-transfer', 'lucro' => 'sack-dollar',
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Gere relatórios operacionais e financeiros.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($types as $key => $label): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card border-0 shadow-petcare h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="text-petcare fs-4"><i class="fa-solid fa-<?= e($icons[$key] ?? 'chart-column') ?>"></i></span>
                    <div class="fw-semibold small"><?= e($label) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-petcare">
    <div class="card-body p-4">
        <h2 class="h5 mb-3">Gerar relatório</h2>
        <form method="get" action="<?= e(url('relatorios.generate')) ?>" class="row g-3">
            <input type="hidden" name="r" value="relatorios.generate">
            <div class="col-md-4">
                <label for="tipo" class="form-label fw-semibold">Tipo</label>
                <select name="tipo" id="tipo" class="form-select" required>
                    <?php foreach ($types as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="inicio" class="form-label fw-semibold">Início</label>
                <input type="date" name="inicio" id="inicio" class="form-control" value="<?= e($inicio) ?>" required>
            </div>
            <div class="col-md-2">
                <label for="fim" class="form-label fw-semibold">Fim</label>
                <input type="date" name="fim" id="fim" class="form-control" value="<?= e($fim) ?>" required>
            </div>
            <div class="col-md-2">
                <label for="formato" class="form-label fw-semibold">Formato</label>
                <select name="formato" id="formato" class="form-select">
                    <option value="tela">Tela</option>
                    <option value="csv">CSV</option>
                    <option value="impressao">Impressão</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="veterinario_id" class="form-label fw-semibold">Veterinário</label>
                <select name="veterinario_id" id="veterinario_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($veterinarios as $vet): ?>
                        <option value="<?= e($vet['id']) ?>"><?= e($vet['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-file-lines me-2"></i>Gerar</button>
            </div>
        </form>
    </div>
</div>
