<?php $superadmin = is_superadmin(); ?>
<div class="auth-card license-expired-card">
    <div class="license-expired-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <h1 class="h4 fw-bold text-danger mb-2">LICENÇA DO PETCARE EXPIRADA</h1>
    <p class="text-muted small mb-4">
        O prazo de funcionamento desta instalação terminou. As funcionalidades administrativas e operacionais foram bloqueadas.
        Os dados da clínica foram preservados e voltarão a ficar disponíveis após a prorrogação da licença.
    </p>

    <dl class="row small text-start mb-4">
        <dt class="col-5 text-muted">Clínica</dt>
        <dd class="col-7"><?= e($status['clinica']['nome'] ?? 'PetCare') ?></dd>
        <dt class="col-5 text-muted">Situação</dt>
        <dd class="col-7"><?= license_badge($status['status'] ?? null) ?></dd>
        <dt class="col-5 text-muted">Vencimento</dt>
        <dd class="col-7"><?= e(date_br($status['vencimento'] ?? null)) ?></dd>
    </dl>

    <?php if ($superadmin): ?>
        <a href="<?= e(url('licenca.index')) ?>" class="btn btn-petcare w-100 py-2">
            <i class="fa-solid fa-key me-2"></i>Acessar licenciamento
        </a>
    <?php else: ?>
        <a href="<?= e(url('auth.login')) ?>" class="btn btn-petcare w-100 py-2 mb-2">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Acesso do proprietário
        </a>
        <a href="<?= e(url('site.home')) ?>" class="btn btn-outline-petcare w-100">
            <i class="fa-solid fa-arrow-left me-2"></i>Voltar ao site
        </a>
        <p class="auth-credentials mt-4 mb-0 text-center">Entre em contato com o proprietário do sistema para regularizar a licença.</p>
    <?php endif; ?>
</div>
