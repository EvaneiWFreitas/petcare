<?php

$title = $title ?? 'Veterinários';
$route = 'veterinarios';
$createRoute = 'veterinarios.create';
$rows = $rows ?? [];
$search = $search ?? '';
$paginator = $paginator ?? null;
$extraFilters = '';
$actionDefs = $actions ?? [];
$actionCell = static function (array $row) use ($actionDefs): string {
    $id = (int) ($row['id'] ?? 0);
    $html = '<div class="d-flex justify-content-end gap-1 flex-wrap">';
    foreach ($actionDefs as $action) {
        $href = url((string) ($action['route'] ?? ''), ['id' => $id]);
        $class = 'btn btn-sm ' . ($action['class'] ?? 'btn-outline-secondary');
        $icon = (string) ($action['icon'] ?? 'circle-info');
        $label = (string) ($action['label'] ?? 'Ação');
        if (($action['method'] ?? 'get') === 'post') {
            $html .= '<form method="post" action="' . e($href) . '" onsubmit="return confirm(\'' . e($action['confirm'] ?? 'Confirma esta ação?') . '\')">'
                . csrf_field()
                . '<button type="submit" class="' . e($class) . '" title="' . e($label) . '"><i class="fa-solid fa-' . e($icon) . '"></i></button></form>';
        } else {
            $html .= '<a href="' . e($href) . '" class="' . e($class) . '" title="' . e($label) . '"><i class="fa-solid fa-' . e($icon) . '"></i></a>';
        }
    }
    return $html . '</div>';
};
$columns = [
    'Nome' => 'nome',
    'CPF' => static fn(array $row): string => e(mask_cpf($row['cpf'] ?? '')),
    'CRMV' => 'crmv',
    'Especialidade' => 'especialidade',
    'Telefone' => 'telefone',
    'E-mail' => 'email',
    'Situação' => static fn(array $row): string => status_badge($row['status'] ?? null),
    'Ações' => $actionCell,
];
$actions = [];

require __DIR__ . '/../common/list.php';
