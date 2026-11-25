<?php
$licencaRoute = \PetCare\Core\Request::route();
$licencaItems = [
    ['label' => 'Visão geral', 'route' => 'licenca.index', 'icon' => 'gauge-high'],
    ['label' => 'Configuração do prazo', 'route' => 'licenca.configuracao', 'icon' => 'clock'],
    ['label' => 'Prorrogação', 'route' => 'licenca.prorrogacao', 'icon' => 'calendar-plus'],
    ['label' => 'Licenças ilimitadas', 'route' => 'licenca.ilimitadas', 'icon' => 'infinity'],
    ['label' => 'Histórico', 'route' => 'licenca.historico', 'icon' => 'clock-rotate-left'],
    ['label' => 'Situação dos sistemas', 'route' => 'licenca.situacao', 'icon' => 'signal'],
];
?>
<ul class="nav nav-pills flex-wrap gap-2 mb-4 licenca-nav" aria-label="Menu de licenciamento">
    <?php foreach ($licencaItems as $item): ?>
        <?php $active = $licencaRoute === $item['route']; ?>
        <li class="nav-item">
            <a href="<?= e(url($item['route'])) ?>" class="nav-link <?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-<?= e($item['icon']) ?> me-2"></i><?= e($item['label']) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
