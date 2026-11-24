<?php

$title = $title ?? 'Prontuários';
$route = 'prontuarios';
$createRoute = can('prontuarios', 'create') ? 'prontuarios.create' : null;
$rows = $rows ?? [];
$search = $search ?? '';
$animalId = $animalId ?? 0;
$paginator = $paginator ?? null;
$actionDefs = $actions ?? [];

$actionCell = static function (array $row) use ($actionDefs): string {
    $html = '<div class="d-flex justify-content-end gap-1 flex-wrap">';
    foreach ($actionDefs as $action) {
        $href = url((string) ($action['route'] ?? ''), ['id' => (int) ($row['id'] ?? 0)]);
        $class = 'btn btn-sm ' . ($action['class'] ?? 'btn-outline-secondary');
        $icon = (string) ($action['icon'] ?? 'circle-info');
        $label = (string) ($action['label'] ?? 'Ação');
        $html .= '<a href="' . e($href) . '" class="' . e($class) . '" title="' . e($label) . '"><i class="fa-solid fa-' . e($icon) . '"></i></a>';
    }
    return $html . '</div>';
};

$columns = [
    'Data' => static fn(array $row): string => e(date_br((string) ($row['data_atendimento'] ?? ''), true)),
    'Pet' => static fn(array $row): string => e($row['pet'] ?? ''),
    'Tutor' => static fn(array $row): string => e($row['tutor'] ?? ''),
    'Veterinário' => static fn(array $row): string => e($row['veterinario'] ?? ''),
    'Diagnóstico' => static fn(array $row): string => e(mb_strimwidth((string) ($row['diagnostico'] ?? ''), 0, 70, '...')),
    'Finalizado' => static fn(array $row): string => (int) ($row['finalizado'] ?? 0) === 1
        ? status_badge('finalizado')
        : '<span class="badge bg-secondary rounded-pill">Em aberto</span>',
    'Ações' => $actionCell,
];
$actions = [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
$html = str_replace('Novo registro', 'Novo prontuário', $html);
if ($animalId > 0) {
    $html = str_replace('&amp;page=', '&amp;animal_id=' . (int) $animalId . '&amp;page=', $html);
}
echo $html;
