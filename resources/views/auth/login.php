<?php
$title = $title ?? 'Entrar';
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
        <span class="auth-card-icon"><i class="fa-solid fa-lock"></i></span>
        <h1 class="h4 fw-bold text-petcare mb-1">Acesso ao sistema</h1>
        <p class="text-muted small mb-0">Entre com sua conta para continuar.</p>
    </div>

    <?php if ($errorMessages !== []): ?>
        <div class="alert alert-danger small" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <span><?= e(implode(' ', $errorMessages)) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('auth.authenticate')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="email" class="form-label small fw-semibold">E-mail</label>
            <div class="input-group input-group-petcare">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" name="email" id="email" class="form-control" value="<?= e(old('email')) ?>" placeholder="seu@email.com" autocomplete="username" required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="senha" class="form-label small fw-semibold">Senha</label>
            </div>
            <div class="input-group input-group-petcare">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="senha" id="senha" class="form-control" placeholder="Digite sua senha" autocomplete="current-password" required>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap">
            <div class="form-check mb-0">
                <input type="checkbox" name="lembrar" value="1" id="lembrar" class="form-check-input" <?= old('lembrar') ? 'checked' : '' ?>>
                <label for="lembrar" class="form-check-label small text-muted">Próximo acesso</label>
            </div>
        </div>

        <button type="submit" class="btn btn-petcare w-100 shadow-petcare py-2">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Entrar
        </button>
    </form>

    <div class="auth-divider"><span>ou</span></div>

    <a href="<?= e(url('portal.login')) ?>" class="btn btn-outline-petcare w-100">
        <i class="fa-solid fa-paw me-2"></i>Sou tutor
    </a>

    <p class="auth-credentials mt-4 mb-0 text-center">
        Acesso inicial: <strong>admin@petcare.com.br</strong> / <strong>PetCare@2026</strong>
    </p>
</div>
