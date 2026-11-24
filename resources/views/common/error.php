<?php
$code = $code ?? 500;
$message = $message ?? 'Ocorreu um problema ao processar sua solicitação.';
?>
<div class="text-center py-4">
    <div class="common-error-icon mb-3">
        <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <div class="common-error-code mb-2"><?= e($code) ?></div>
    <p class="text-muted mb-4"><?= e($message) ?></p>
    <a href="<?= e(url('dashboard.index')) ?>" class="btn btn-petcare shadow-sm">
        <i class="fa-solid fa-house me-2"></i>Voltar ao painel
    </a>
</div>
