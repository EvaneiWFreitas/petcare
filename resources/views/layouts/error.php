<?php
$title = $title ?? 'Erro';
$code = $code ?? 500;
$message = $message ?? 'Ocorreu um problema ao processar sua solicitação.';
$trace = $trace ?? '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | PetCare</title>
    <link rel="icon" href="<?= e(favicon_url()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="error-body">
    <main class="error-shell">
        <div class="error-card">
            <div class="auth-brand-logo justify-content-center">
                <?= brand_logo('brand-logo-img', 'auth-logo-icon') ?>
                <span class="auth-logo-text">PetCare</span>
            </div>
            <div class="error-code"><?= e($code) ?></div>
            <h1 class="h4 fw-bold text-petcare mb-2"><?= e($title) ?></h1>
            <p class="text-muted mb-4"><?= e($message) ?></p>

            <?= $content ?>

            <a href="<?= e(url('dashboard.index')) ?>" class="btn btn-petcare shadow-petcare">
                <i class="fa-solid fa-house me-2"></i>Voltar ao painel
            </a>

            <?php if (!empty($trace)): ?>
                <div class="mt-4 text-start">
                    <div class="small text-muted mb-2">Detalhes técnicos</div>
                    <pre class="error-trace small mb-0"><?= e($trace) ?></pre>
                </div>
            <?php endif; ?>
        </div>
        <p class="error-footer">PetCare &copy; <?= e(date('Y')) ?> — Cuidando de quem faz parte da sua família.</p>
    </main>
</body>
</html>
