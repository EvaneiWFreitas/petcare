<?php
$title = $title ?? 'PetCare';
$flashes = flash_messages();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Acesso ao sistema PetCare">
    <link rel="icon" href="<?= e(favicon_url()) ?>">
    <title><?= e($title) ?> | PetCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-shell">
        <section class="auth-brand" aria-label="Sobre o PetCare">
            <div class="auth-brand-content">
                <div class="auth-brand-logo">
                    <?= brand_logo('brand-logo-img', 'auth-logo-icon') ?>
                    <span class="auth-logo-text">PetCare</span>
                </div>
                <h1 class="auth-brand-title">Cuidando de quem faz parte da sua família.</h1>
                <p class="auth-brand-text">Agenda, prontuários, vacinas e gestão clínica em um só lugar. Mais tempo para o que realmente importa: o bem-estar dos seus pacientes.</p>
                <div class="auth-brand-features">
                    <span><i class="fa-solid fa-check me-2"></i>Agenda integrada</span>
                    <span><i class="fa-solid fa-check me-2"></i>Prontuários digitais</span>
                    <span><i class="fa-solid fa-check me-2"></i>Controle de estoque</span>
                </div>
            </div>
            <div class="auth-brand-art" aria-hidden="true">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>
        </section>

        <main class="auth-panel">
            <?php if ($flashes !== []): ?>
                <div class="flash-stack" aria-live="polite">
                    <?php foreach ($flashes as $flash): ?>
                        <?php
                        $type = $flash['type'] ?? 'info';
                        $alertType = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
                        $alertClass = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'][$alertType];
                        $alertIcon = ['success' => 'check', 'error' => 'xmark', 'warning' => 'triangle-exclamation', 'info' => 'circle-info'][$alertType];
                        ?>
                        <div class="alert alert-<?= e($alertClass) ?> alert-dismissible fade show shadow-sm auto-hide-alert" role="alert">
                            <i class="fa-solid fa-<?= e($alertIcon) ?> me-2"></i><?= e($flash['message'] ?? '') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?= $content ?>

            <p class="auth-footer-note">PetCare &copy; <?= e(date('Y')) ?> — Cuidando de quem faz parte da sua família.</p>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
