<?php
$title = $title ?? 'Documento';
$currentUser = user();
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
<body class="print-body">
    <div class="print-toolbar no-print">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <?= brand_logo('brand-logo-img', 'print-toolbar-logo') ?>
                <strong class="text-petcare">PetCare</strong>
                <span class="text-muted small d-none d-sm-inline">Documento para impressão</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.history.back()">
                    <i class="fa-solid fa-arrow-left me-1"></i>Voltar
                </button>
                <button type="button" class="btn btn-petcare btn-sm shadow-sm" onclick="window.print()">
                    <i class="fa-solid fa-print me-2"></i>Imprimir / Salvar PDF
                </button>
            </div>
        </div>
    </div>

    <main class="print-page">
        <header class="print-header">
            <div class="print-header-brand">
                <?= brand_logo('brand-logo-img brand-logo-img-lg', 'print-header-logo') ?>
                <div>
                    <strong>PetCare</strong>
                    <small class="d-block text-muted">Clínica Veterinária</small>
                </div>
            </div>
            <div class="text-end small text-muted">
                <div><?= e(date_br(date('Y-m-d'))) ?></div>
                <?php if (!empty($currentUser['nome'])): ?>
                    <div>Responsável: <?= e($currentUser['nome']) ?></div>
                <?php endif; ?>
            </div>
        </header>

        <h1 class="print-title"><?= e($title) ?></h1>

        <?= $content ?>

        <footer class="print-footer">
            <span>PetCare — Cuidando de quem faz parte da sua família.</span>
            <span>&copy; <?= e(date('Y')) ?></span>
        </footer>
    </main>
</body>
</html>
