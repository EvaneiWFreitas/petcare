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
<nav class="navbar navbar-expand-lg site-nav sticky-top">
    <div class="container py-1">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('site.home')) ?>">
            <?php if ($siteLogo !== ''): ?>
                <img src="<?= e(upload_url($siteLogo)) ?>" alt="PetCare" class="brand-logo-img brand-logo-img-sm">
            <?php else: ?>
                <span class="brand-mark"><i class="fa-solid fa-paw"></i></span>
            <?php endif; ?>
            <span>PetCare</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none px-2" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-4 mt-3 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link<?= $active('site.home', 'home') ?>" href="<?= e(url('site.home')) ?>">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $active('site.profissional', 'profissional') ?>" href="<?= e(url('site.profissional')) ?>">Profissionais</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(url('portal.login')) ?>">Portal do tutor</a>
                </li>
                <li class="nav-item mt-3 mt-lg-0 ms-lg-2">
                    <a class="btn btn-petcare" href="<?= e(url('auth.login')) ?>">
                        Acessar sistema
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main>
    <?= $content ?>
</main>

<footer class="site-footer pt-5 pb-4">
    <div class="container">
        <div class="row g-4 pb-4 mb-4 border-bottom">
            <div class="col-lg-5">
                <a class="site-footer-brand d-inline-flex align-items-center gap-2 mb-3" href="<?= e(url('site.home')) ?>">
                    <?php if ($siteLogo !== ''): ?>
                        <img src="<?= e(upload_url($siteLogo)) ?>" alt="PetCare" class="brand-logo-img brand-logo-img-sm">
                    <?php else: ?>
                        <span class="brand-mark"><i class="fa-solid fa-paw"></i></span>
                    <?php endif; ?>
                    <span>PetCare</span>
                </a>
                <p class="text-secondary mb-0 pe-lg-5">Plataforma completa para gestão veterinária. Mais organização para a clínica, mais cuidado para os pets.</p>
            </div>
            <div class="col-6 col-lg-3 offset-lg-1">
                <div class="fw-semibold text-dark mb-3 small text-uppercase" style="letter-spacing:.06em;">Navegação</div>
                <div class="d-flex flex-column gap-2 site-footer-links">
                    <a href="<?= e(url('site.home')) ?>">Início</a>
                    <a href="<?= e(url('site.profissional')) ?>">Profissionais</a>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="fw-semibold text-dark mb-3 small text-uppercase" style="letter-spacing:.06em;">Acesso</div>
                <div class="d-flex flex-column gap-2 site-footer-links">
                    <a href="<?= e(url('auth.login')) ?>">Sistema</a>
                    <a href="<?= e(url('portal.login')) ?>">Portal do tutor</a>
                </div>
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 site-footer-bottom">
            <span>&copy; <?= date('Y') ?> PetCare. Todos os direitos reservados.</span>
            <span>Cuidando de quem faz parte da sua família.</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>