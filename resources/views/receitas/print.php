<?php

$receita = $receita ?? [];
$itens = $itens ?? [];
$clinica = setting('clinica_nome', 'PetCare');
$logo = setting('logo');
$cnpj = setting('cnpj');
$endereco = setting('endereco');
$telefone = setting('telefone');
$whatsapp = setting('whatsapp');
$emailContato = setting('email');
$controlado = (int) ($receita['controle_especial'] ?? 0) === 1;
?>

<div class="d-flex justify-content-between align-items-start gap-3 mb-4 no-print">
    <div class="text-muted small"><i class="fa-solid fa-circle-info me-1"></i>Confira os dados antes de imprimir.</div>
    <button type="button" class="btn btn-petcare btn-sm shadow-sm" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Imprimir / Salvar PDF</button>
</div>

<div class="border-bottom border-2 pb-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <?php if ($logo !== ''): ?>
            <img src="<?= e(upload_url($logo)) ?>" alt="<?= e($clinica) ?>" class="brand-logo-img brand-logo-img-lg">
        <?php else: ?>
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-petcare text-white" style="width:56px;height:56px;font-size:1.6rem"><i class="fa-solid fa-paw"></i></span>
        <?php endif; ?>
        <div>
            <div class="fs-4 fw-bold text-petcare mb-0"><?= e($clinica) ?></div>
            <div class="text-muted small">Clínica Veterinária</div>
        </div>
    </div>
    <div class="text-end small">
        <div><strong>CNPJ:</strong> <?= e($cnpj) ?></div>
        <div><?= e($endereco) ?></div>
        <div><?= e($telefone) ?> <?= $whatsapp !== '' ? ' · WhatsApp ' . e($whatsapp) : '' ?></div>
        <div><?= e($emailContato) ?></div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h2 class="h5 mb-0 fw-bold text-petcare">Receita nº <?= e($receita['codigo'] ?? '') ?></h2>
    <span class="badge bg-light text-dark border"><?= e(date_br($receita['data_prescricao'] ?? null)) ?></span>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <h3 class="h6 fw-bold text-petcare text-uppercase"><i class="fa-solid fa-user me-2"></i>Tutor</h3>
        <p class="small mb-1"><strong><?= e($receita['tutor'] ?? '') ?></strong></p>
        <p class="small text-muted mb-1"><?= e($receita['tutor_telefone'] ?? '') ?></p>
        <p class="small text-muted mb-0"><?= e($receita['tutor_email'] ?? '') ?></p>
    </div>
    <div class="col-md-4">
        <h3 class="h6 fw-bold text-petcare text-uppercase"><i class="fa-solid fa-dog me-2"></i>Animal</h3>
        <p class="small mb-1"><strong><?= e($receita['pet'] ?? '') ?></strong></p>
        <p class="small text-muted mb-0">Registro: <?= e($receita['animal_id'] ?? '') ?></p>
    </div>
    <div class="col-md-4">
        <h3 class="h6 fw-bold text-petcare text-uppercase"><i class="fa-solid fa-user-doctor me-2"></i>Veterinário</h3>
        <p class="small mb-1"><strong><?= e($receita['veterinario'] ?? '') ?></strong></p>
        <p class="small text-muted mb-1">CRMV/UF: <?= e($receita['crmv'] ?? '') ?></p>
        <p class="small text-muted mb-0"><?= e($receita['cargo'] ?? '') ?><?= !empty($receita['especialidade']) ? ' · ' . e($receita['especialidade']) : '' ?></p>
    </div>
</div>

<table class="table table-bordered mb-4">
    <thead class="table-light">
        <tr>
            <th class="small text-uppercase fw-bold">Medicamento</th>
            <th class="small text-uppercase fw-bold">Dosagem</th>
            <th class="small text-uppercase fw-bold">Via</th>
            <th class="small text-uppercase fw-bold">Frequência</th>
            <th class="small text-uppercase fw-bold">Duração</th>
            <th class="small text-uppercase fw-bold">Observações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($itens === []): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum medicamento registrado.</td></tr>
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

<div class="mb-4">
    <h3 class="h6 fw-bold text-petcare text-uppercase">Orientações</h3>
    <p class="small mb-0" style="white-space: pre-line"><?= e($receita['orientacoes'] ?? '') ?></p>
</div>

<?php if ($controlado): ?>
    <div class="alert alert-warning border small mb-4">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        Prescrição sujeita a controle especial. A dispensação deve observar a legislação vigente e a retenção da via do estabelecimento, quando aplicável.
    </div>
<?php endif; ?>

<div class="row mt-5 pt-4">
    <div class="col-md-6 offset-md-6 border-top pt-2 text-center small">
        <div><?= e($receita['assinatura'] ?? ($receita['veterinario'] ?? '')) ?></div>
        <div class="text-muted"><?= e($receita['veterinario'] ?? '') ?> — CRMV/UF <?= e($receita['crmv'] ?? '') ?></div>
        <div class="text-muted">Responsável pela prescrição</div>
    </div>
</div>
