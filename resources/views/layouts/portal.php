<?php
$title = $title ?? 'Portal do Tutor';
$currentRoute = \PetCare\Core\Request::route();
$flashes = flash_messages();
$tutor = portal_user();
$tutorName = trim((string) ($tutor['nome'] ?? '')) ?: 'Tutor';
$tutorFirstName = explode(' ', $tutorName)[0] ?: 'Tutor';
$tutorEmail = trim((string) ($tutor['email'] ?? ''));
$links = [
    ['label' => 'Início', 'route' => 'portal.index', 'icon' => 'house'],
    ['label' => 'Animais', 'route' => 'portal.animais', 'icon' => 'paw'],
    ['label' => 'Agendamentos', 'route' => 'portal.agendamentos', 'icon' => 'calendar-days'],
    ['label' => 'Vacinas', 'route' => 'portal.vacinas', 'icon' => 'syringe'],
    ['label' => 'Receitas', 'route' => 'portal.receitas', 'icon' => 'prescription-bottle'],
    ['label' => 'Exames', 'route' => 'portal.exames', 'icon' => 'microscope'],
    ['label' => 'Pagamentos', 'route' => 'portal.pagamentos', 'icon' => 'credit-card'],
    ['label' => 'Perfil', 'route' => 'portal.perfil', 'icon' => 'user'],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal do tutor PetCare">
    <title><?= e($title) ?> | PetCare</title>
    <link rel="icon" href="<?= e(favicon_url()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="portal-body">
    <header class="portal-header">
        <div class="container-fluid d-flex flex-wrap align-items-center gap-3">
            <a href="<?= e(url('portal.index')) ?>" class="portal-brand text-decoration-none">
                <?= brand_logo('brand-logo-img', 'portal-brand-icon') ?>
                <span>
                    <strong>PetCare</strong>
                    <small class="d-block text-muted">Portal do Tutor</small>
                </span>
            </a>

            <nav class="portal-nav flex-grow-1" aria-label="Navegação do portal">
                <?php foreach ($links as $link): ?>
                    <?php $active = $currentRoute === $link['route']; ?>
                    <a href="<?= e(url($link['route'])) ?>" class="portal-nav-link <?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid fa-<?= e($link['icon']) ?> me-1"></i><?= e($link['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ($tutor !== null): ?>
                <div class="dropdown">
                    <button type="button" class="portal-user d-flex align-items-center gap-2 border-0 bg-transparent p-0" id="portalUserMenu" data-bs-toggle="dropdown" aria-expanded="false" title="Menu do tutor">
                        <?php if (!empty($tutor['foto'])): ?>
                            <img src="<?= e(upload_url($tutor['foto'])) ?>" alt="<?= e($tutorName) ?>" class="user-avatar rounded-circle border shadow-sm">
                        <?php else: ?>
                            <span class="user-avatar-fallback rounded-circle bg-petcare-soft d-flex align-items-center justify-content-center text-petcare fw-bold"><?= e(mb_strtoupper(mb_substr($tutorFirstName, 0, 1))) ?></span>
                        <?php endif; ?>
                        <span class="min-w-0">
                            <span class="fw-semibold d-block text-truncate"><?= e($tutorName) ?></span>
                            <small class="text-muted d-none d-md-block text-truncate"><?= e($tutorEmail !== '' ? $tutorEmail : 'Tutor') ?></small>
                        </span>
                        <i class="fa-solid fa-chevron-down fa-xs ms-1"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="portalUserMenu">
                        <li>
                            <a class="dropdown-item" href="<?= e(url('portal.perfil')) ?>">
                                <i class="fa-solid fa-user fa-fw me-2"></i>Meu perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= e(url('site.home')) ?>">
                                <i class="fa-solid fa-arrow-left fa-fw me-2"></i>Voltar ao site
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= e(url('portal.logout')) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item">
                                    <i class="fa-solid fa-right-from-bracket fa-fw me-2"></i>Sair
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= e(url('site.home')) ?>" class="btn btn-outline-petcare btn-sm" title="Voltar ao site" aria-label="Voltar ao site">
                    <i class="fa-solid fa-arrow-left me-1"></i><span class="d-none d-sm-inline">Voltar ao site</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($flashes !== []): ?>
        <div class="container-fluid mt-3 flash-stack" aria-live="polite">
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

    <main class="portal-content">
        <div class="container-fluid">
            <?= $content ?>
        </div>
    </main>

    <footer class="portal-footer">
        <div class="container-fluid d-flex flex-wrap justify-content-between gap-2">
            <span><strong>PetCare</strong> — Cuidando de quem faz parte da sua família.</span>
            <span>&copy; <?= e(date('Y')) ?> PetCare</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
