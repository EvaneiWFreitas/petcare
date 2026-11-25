<?php
$title = $title ?? 'PetCare';
$currentRoute = \PetCare\Core\Request::route();
$currentUser = user();
$userName = trim((string) ($currentUser['nome'] ?? 'Usuário'));
$firstName = explode(' ', $userName)[0] ?: 'Usuário';

$menu = [
    'Gestão Clínica' => [
        ['label' => 'Painel', 'route' => 'dashboard.index', 'icon' => 'gauge-high'],
        ['label' => 'Clientes', 'route' => 'clientes.index', 'icon' => 'users', 'permission' => ['clientes', 'view']],
        ['label' => 'Animais', 'route' => 'animais.index', 'icon' => 'paw', 'permission' => ['animais', 'view']],
        ['label' => 'Agenda', 'route' => 'agenda.index', 'icon' => 'calendar-days', 'permission' => ['agenda', 'view']],
        ['label' => 'Atendimentos', 'route' => 'atendimentos.index', 'icon' => 'stethoscope', 'permission' => ['atendimentos', 'view']],
        ['label' => 'Prontuários', 'route' => 'prontuarios.index', 'icon' => 'file-medical', 'permission' => ['prontuarios', 'view']],
        ['label' => 'Vacinas', 'route' => 'vacinas.index', 'icon' => 'syringe', 'permission' => ['vacinas', 'view']],
        ['label' => 'Carteira de vacinação', 'route' => 'vacinas.carteira', 'icon' => 'book-medical', 'permission' => ['vacinas', 'print']],
        ['label' => 'Receitas', 'route' => 'receitas.index', 'icon' => 'prescription-bottle', 'permission' => ['receitas', 'view']],
        ['label' => 'Exames', 'route' => 'exames.index', 'icon' => 'microscope', 'permission' => ['exames', 'view']],
        ['label' => 'Cirurgias', 'route' => 'cirurgias.index', 'icon' => 'scalpel', 'permission' => ['cirurgias', 'view']],
        ['label' => 'Internações', 'route' => 'internacoes.index', 'icon' => 'bed-medical', 'permission' => ['internacoes', 'view']],
    ],
    'Operação' => [
        ['label' => 'Estoque / Farmácia', 'route' => 'estoque.index', 'icon' => 'boxes-stacked', 'permission' => ['farmacia', 'view']],
        ['label' => 'Compras', 'route' => 'compras.index', 'icon' => 'cart-shopping', 'permission' => ['compras', 'view']],
        ['label' => 'Financeiro', 'route' => 'financeiro.index', 'icon' => 'chart-line', 'permission' => ['financeiro', 'view']],
        ['label' => 'Banho e Tosa', 'route' => 'banho_tosa.index', 'icon' => 'soap', 'permission' => ['banho_tosa', 'view']],
        ['label' => 'Relatórios', 'route' => 'relatorios.index', 'icon' => 'chart-pie', 'permission' => ['relatorios', 'view']],
        ['label' => 'Notificações', 'route' => 'notificacoes.index', 'icon' => 'bell'],
    ],
    'Administração' => [
        ['label' => 'Usuários', 'route' => 'usuarios.index', 'icon' => 'user-gear', 'permission' => ['usuarios', 'view']],
        ['label' => 'Veterinários', 'route' => 'veterinarios.index', 'icon' => 'user-doctor', 'permission' => ['usuarios', 'view']],
        ['label' => 'Configurações', 'route' => 'config.index', 'icon' => 'gear', 'permission' => ['configuracoes', 'view']],
    ],
];

if (is_superadmin()) {
    $menu = [
        'LICENCIAMENTO E CONTROLE DO SISTEMA' => [
            ['label' => 'Visão geral', 'route' => 'licenca.index', 'icon' => 'gauge-high'],
            ['label' => 'Configuração do prazo', 'route' => 'licenca.configuracao', 'icon' => 'clock'],
            ['label' => 'Prorrogação de licenças', 'route' => 'licenca.prorrogacao', 'icon' => 'calendar-plus'],
            ['label' => 'Licenças ilimitadas', 'route' => 'licenca.ilimitadas', 'icon' => 'infinity'],
            ['label' => 'Histórico de alterações', 'route' => 'licenca.historico', 'icon' => 'clock-rotate-left'],
            ['label' => 'Situação dos sistemas licenciados', 'route' => 'licenca.situacao', 'icon' => 'signal'],
        ],
    ] + $menu;
}

$visibleMenu = [];
foreach ($menu as $groupLabel => $items) {
    $visibleItems = array_values(array_filter($items, static function (array $item): bool {
        return empty($item['permission']) || can($item['permission'][0], $item['permission'][1]);
    }));
    if ($visibleItems !== []) {
        $visibleMenu[$groupLabel] = $visibleItems;
    }
}

$catalogItems = [
    ['label' => 'Espécies', 'route' => 'crud.especies.index', 'icon' => 'paw', 'permission' => ['configuracoes', 'view']],
    ['label' => 'Raças', 'route' => 'crud.racas.index', 'icon' => 'dog', 'permission' => ['configuracoes', 'view']],
    ['label' => 'Catálogo de Vacinas', 'route' => 'crud.catalogo-vacinas.index', 'icon' => 'syringe', 'permission' => ['configuracoes', 'view']],
    ['label' => 'Categorias', 'route' => 'crud.categorias.index', 'icon' => 'tags', 'permission' => ['configuracoes', 'view']],
    ['label' => 'Fornecedores', 'route' => 'crud.fornecedores.index', 'icon' => 'truck', 'permission' => ['compras', 'view']],
    ['label' => 'Serviços', 'route' => 'crud.servicos.index', 'icon' => 'soap', 'permission' => ['banho_tosa', 'view']],
    ['label' => 'Serviços do site', 'route' => 'crud.site-servicos.index', 'icon' => 'globe', 'permission' => ['configuracoes', 'view']],
    ['label' => 'Carrossel da p?gina inicial', 'route' => 'crud.site-carrossel.index', 'icon' => 'images', 'permission' => ['configuracoes', 'view']],
];
$catalogItems = array_values(array_filter($catalogItems, static function (array $item): bool {
    return can($item['permission'][0], $item['permission'][1]);
}));
$catalogRoutes = array_column($catalogItems, 'route');
$catalogActive = in_array($currentRoute, $catalogRoutes, true);

$flashes = flash_messages();
$licenseAlert = is_superadmin() ? null : license_warning();
$year = date('Y');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PetCare - gestão clínica veterinária">
    <title><?= e($title) ?> | PetCare</title>
    <link rel="icon" href="<?= e(favicon_url()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <aside class="sidebar" id="sidebar" aria-label="Menu lateral">
        <div class="sidebar-brand">
            <?= brand_logo('brand-logo-img', 'sidebar-brand-icon') ?>
            <span>
                <strong>PetCare</strong>
                <small>Clínica Veterinária</small>
            </span>
        </div>

        <nav class="sidebar-nav" aria-label="Navegação principal">
            <?php foreach ($visibleMenu as $groupLabel => $items): ?>
                <p class="sidebar-heading"><?= e($groupLabel) ?></p>
                <?php foreach ($items as $item): ?>
                    <?php
                    $prefix = explode('.', $item['route'], 2)[0];
                    $active = $currentRoute === $item['route'] || ($item['route'] !== 'dashboard.index' && str_starts_with($currentRoute, $prefix . '.'));
                    ?>
                    <a href="<?= e(url($item['route'])) ?>" class="sidebar-link <?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid fa-<?= e($item['icon']) ?> fa-fw me-2"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?php if ($catalogItems !== []): ?>
                <p class="sidebar-heading">Catálogos</p>
                <a class="sidebar-link d-flex align-items-center justify-content-between <?= $catalogActive ? 'active' : '' ?>" data-bs-toggle="collapse" href="#menuCatalogos" role="button" aria-expanded="<?= $catalogActive ? 'true' : 'false' ?>" aria-controls="menuCatalogos">
                    <span><i class="fa-solid fa-list-check fa-fw me-2"></i><span>Catálogos</span></span>
                    <i class="fa-solid fa-chevron-down fa-xs"></i>
                </a>
                <div class="collapse <?= $catalogActive ? 'show' : '' ?>" id="menuCatalogos">
                    <div class="sidebar-submenu">
                        <?php foreach ($catalogItems as $item): ?>
                            <?php $active = $currentRoute === $item['route']; ?>
                            <a href="<?= e(url($item['route'])) ?>" class="sidebar-link sidebar-sublink <?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                                <i class="fa-solid fa-<?= e($item['icon']) ?> fa-fw me-2"></i>
                                <span><?= e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <i class="fa-solid fa-heart-pulse me-2"></i>
            <span>Vida bem cuidada</span>
        </div>
    </aside>

    <div class="app-wrapper">
        <header class="app-header">
            <div class="container-fluid d-flex align-items-center gap-2 gap-md-3">
                <button type="button" class="btn btn-light border-0 shadow-sm d-lg-none" id="sidebarToggle" aria-label="Abrir menu" aria-controls="sidebar" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="dropdown flex-grow-1 min-w-0">
                    <button type="button" class="user-menu-trigger d-flex align-items-center gap-2 border-0 bg-transparent p-0" id="adminUserMenu" data-bs-toggle="dropdown" aria-expanded="false" title="Menu do usuário">
                        <?php if (!empty($currentUser['foto'])): ?>
                            <img src="<?= e(upload_url($currentUser['foto'])) ?>" alt="<?= e($userName) ?>" class="user-avatar rounded-circle border shadow-sm">
                        <?php else: ?>
                            <span class="user-avatar-fallback rounded-circle bg-petcare-soft d-flex align-items-center justify-content-center text-petcare fw-bold"><?= e(mb_strtoupper(mb_substr($firstName, 0, 1))) ?></span>
                        <?php endif; ?>
                        <span class="min-w-0 text-start">
                            <span class="small text-muted d-none d-sm-block">Bem-vindo de volta,</span>
                            <span class="fw-semibold text-petcare text-truncate d-block"><?= e($firstName) ?></span>
                        </span>
                        <i class="fa-solid fa-chevron-down fa-xs ms-1"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="adminUserMenu">
                        <li>
                            <a class="dropdown-item" href="<?= e(url('auth.change-password')) ?>">
                                <i class="fa-solid fa-key fa-fw me-2"></i>Trocar senha
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= e(url('site.home')) ?>">
                                <i class="fa-solid fa-arrow-left fa-fw me-2"></i>Voltar ao site
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= e(url('auth.logout')) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item">
                                    <i class="fa-solid fa-right-from-bracket fa-fw me-2"></i>Sair
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                <?php if (!empty($currentUser['perfil_nome'])): ?>
                    <span class="badge rounded-pill bg-petcare-soft d-none d-md-inline"><?= e($currentUser['perfil_nome']) ?></span>
                <?php endif; ?>
                <a href="<?= e(url('notificacoes.index')) ?>" class="btn btn-light border-0 shadow-sm icon-button" title="Notificações" aria-label="Notificações">
                    <i class="fa-regular fa-bell"></i>
                </a>
            </div>
        </header>

        <?php if ($flashes !== []): ?>
            <div class="flash-stack" aria-live="polite">
                <?php foreach ($flashes as $flash): ?>
                    <?php
                    $type = $flash['type'] ?? 'info';
                    $alertType = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
                    $alertClass = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'][$alertType];
                    $alertIcon = ['success' => 'check', 'error' => 'xmark', 'warning' => 'triangle-exclamation', 'info' => 'circle-info'][$alertType];
                    ?>
                    <div class="alert alert-<?= e($alertClass) ?> alert-dismissible fade show shadow-petcare auto-hide-alert" role="alert">
                        <i class="fa-solid fa-<?= e($alertIcon) ?> me-2"></i><?= e($flash['message'] ?? '') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($licenseAlert !== null): ?>
            <div class="container-fluid mt-3">
                <div class="alert alert-<?= e($licenseAlert['level']) ?> border-0 shadow-petcare license-warning mb-0" role="alert">
                    <i class="fa-solid fa-clock fa-fw me-2"></i>
                    <strong>Aviso de licença:</strong> <?= e($licenseAlert['message']) ?>
                </div>
            </div>
        <?php endif; ?>

        <main class="app-content">
            <div class="container-fluid">
                <?= $content ?>
            </div>
        </main>

        <footer class="app-footer">
            <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><strong>PetCare</strong> — Cuidando de quem faz parte da sua família.</span>
                <span>&copy; <?= e($year) ?> PetCare. Todos os direitos reservados.</span>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
