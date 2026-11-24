<?php
$title = $title ?? 'PetCare';
$currentRoute = \PetCare\Core\Request::route();
$siteLogo = setting('logo');
$active = static function (string ...$routes) use ($currentRoute): string {
    return in_array($currentRoute, $routes, true) ? ' active' : '';
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PetCare — sistema de gestão veterinária para clínicas, profissionais e tutores.">
    <link rel="icon" href="<?= e(favicon_url()) ?>">
    <title><?= e($title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/site.css')) ?>" rel="stylesheet">
</head>
<body class="site-body">
<nav class="navbar navbar-expand-lg navbar-light bg-white site-nav shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(url('site.home')) ?>">
            <?php if ($siteLogo !== ''): ?><img src="<?= e(upload_url($siteLogo)) ?>" alt="PetCare" class="brand-logo-img brand-logo-img-sm me-2"><?php else: ?><i class="fa-solid fa-paw text-petcare me-2"></i><?php endif; ?>PetCare
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                <li class="nav-item">
                    <a class="nav-link<?= $active('site.home', 'home') ?>" href="<?= e(url('site.home')) ?>">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $active('site.profissional', 'profissional') ?>" href="<?= e(url('site.profissional')) ?>">Profissional</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(url('portal.login')) ?>">Portal do tutor</a>
                </li>
                <li class="nav-item mt-2 mt-lg-0">
                    <a class="btn btn-petcare btn-sm px-3" href="<?= e(url('auth.login')) ?>">
                        <i class="fa-solid fa-right-to-bracket me-1"></i>Acessar sistema
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main>
    <?= $content ?>
</main>

<footer class="site-footer py-4 mt-5">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="fw-semibold"><?php if ($siteLogo !== ''): ?><img src="<?= e(upload_url($siteLogo)) ?>" alt="PetCare" class="brand-logo-img brand-logo-img-sm me-2"><?php else: ?><i class="fa-solid fa-paw text-petcare me-2"></i><?php endif; ?>PetCare</span>
            <span class="text-secondary small">Cuidando de quem faz parte da sua família.</span>
            <span class="text-secondary small">&copy; <?= date('Y') ?> PetCare</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
