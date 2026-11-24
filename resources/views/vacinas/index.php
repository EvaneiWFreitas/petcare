<?php

$alertas = $alertas ?? ['atrasadas' => 0, 'proximas' => 0];
$rows = $rows ?? [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
$needle = 'href="' . e(url('vacinas.carteira')) . '"';
foreach ($rows as $row) {
    $position = strpos($html, $needle);
    if ($position === false) {
        continue;
    }
    $animalId = (int) ($row['animal_id'] ?? 0);
    $target = 'href="' . e(url('vacinas.carteira', ['animal_id' => $animalId])) . '"';
    $html = substr_replace($html, $target, $position, strlen($needle));
}
?>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="text-danger"><i class="fa-solid fa-triangle-exclamation fa-2x"></i></span>
                <div><div class="h4 mb-0 fw-bold"><?= (int) ($alertas['atrasadas'] ?? 0) ?></div><small class="text-muted">Doses atrasadas</small></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="text-warning"><i class="fa-solid fa-clock fa-2x"></i></span>
                <div><div class="h4 mb-0 fw-bold"><?= (int) ($alertas['proximas'] ?? 0) ?></div><small class="text-muted">Doses nos próximos 30 dias</small></div>
            </div>
        </div>
    </div>
</div>

<?= $html ?>
