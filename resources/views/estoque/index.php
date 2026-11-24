<?php

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
$html = str_replace('Novo registro', 'Novo produto', $html);
if (($situacao ?? 'todos') !== 'todos') {
    $html = str_replace('&amp;page=', '&amp;situacao=' . rawurlencode((string) $situacao) . '&amp;page=', $html);
}

$summary = $summary ?? [];
$cards = [
    ['label' => 'Total de itens', 'value' => (int) ($summary['total'] ?? 0), 'icon' => 'boxes-stacked', 'color' => 'primary'],
    ['label' => 'Estoque baixo', 'value' => (int) ($summary['baixo'] ?? 0), 'icon' => 'triangle-exclamation', 'color' => 'danger'],
    ['label' => 'Vencendo em 30 dias', 'value' => (int) ($summary['vencendo'] ?? 0), 'icon' => 'hourglass', 'color' => 'warning'],
    ['label' => 'Vencidos', 'value' => (int) ($summary['vencido'] ?? 0), 'icon' => 'calendar-xmark', 'color' => 'danger'],
    ['label' => 'Valor em estoque', 'value' => money($summary['valor_total'] ?? 0), 'icon' => 'sack-dollar', 'color' => 'success'],
];
?>
<div class="row g-3 mb-4">
    <?php foreach ($cards as $card): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card border-0 shadow-petcare h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span class="stat-card-label"><?= e($card['label']) ?></span>
                        <span class="stat-card-icon text-<?= e($card['color']) ?>"><i class="fa-solid fa-<?= e($card['icon']) ?>"></i></span>
                    </div>
                    <div class="stat-card-value mt-2"><?= e($card['value']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?= $html ?>
