<?php
$title = $title ?? 'Alterar senha';
$errors = $errors ?? [];
$errorMessages = [];
foreach ($errors as $field => $error) {
    if (is_array($error)) {
        foreach ($error as $item) {
            $errorMessages[] = (string) $item;
        }
    } elseif (is_string($error)) {
        $errorMessages[] = $error;
    }
}
?>
<div class="auth-card">
    <div class="auth-card-header">
        <span class="auth-card-icon"><i class="fa-solid fa-key"></i></span>
        <h1 class="h4 fw-bold text-petcare mb-1">Alterar senha</h1>
        <p class="text-muted small mb-0">Escolha uma nova senha para a sua conta.</p>
    </div>

    <?php if ($errorMessages !== []): ?>
        <div class="alert alert-danger small" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <span><?= e(implode(' ', $errorMessages)) ?></span>
        </div>
    <?php endif; ?>

    <div class="alert alert-info small" role="alert">
        <i class="fa-solid fa-circle-info me-2"></i>A senha deve ter no mínimo 8 caracteres.
    </div>

    <form method="post" action="<?= e(url('auth.update-password')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="senha_atual" class="form-label small fw-semibold">Senha atual</label>
            <div class="input-group input-group-petcare">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="senha_atual" id="senha_atual" class="form-control" autocomplete="current-password" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="senha_nova" class="form-label small fw-semibold">Nova senha</label>
            <div class="input-group input-group-petcare">
                <span class="input-group-text"><i class="fa-solid fa-lock-open"></i></span>
                <input type="password" name="senha_nova" id="senha_nova" class="form-control" autocomplete="new-password" minlength="8" required>
            </div>
        </div>

        <div class="mb-4">
            <label for="senha_confirmacao" class="form-label small fw-semibold">Confirmar nova senha</label>
            <div class="input-group input-group-petcare">
                <span class="input-group-text"><i class="fa-solid fa-check-double"></i></span>
                <input type="password" name="senha_confirmacao" id="senha_confirmacao" class="form-control" autocomplete="new-password" minlength="8" required>
            </div>
        </div>

        <button type="submit" class="btn btn-petcare w-100 shadow-petcare py-2">
            <i class="fa-solid fa-floppy-disk me-2"></i>Salvar nova senha
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="<?= e(url('dashboard.index')) ?>" class="small text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i>Voltar ao painel</a>
    </div>
</div>
