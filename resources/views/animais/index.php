<?php

$title = $title ?? 'Animais';
$route = 'animais';
$createRoute = 'animais.create';
$rows = $rows ?? [];
$search = $search ?? '';
$especieId = (int) ($especie_id ?? 0);
$paginator = $paginator ?? null;
$extraFilters = $extraFilters ?? '';
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
    'Código' => 'codigo',
    'Pet' => static function (array $row): string {
        $name = e($row['nome'] ?? '');
        if (!empty($row['foto'])) {
            return '<img src="' . e(upload_url($row['foto'])) . '" alt="' . $name . '" width="36" height="36" class="rounded object-fit-cover me-2">' . $name;
        }
        return '<i class="fa-solid fa-paw text-petcare me-2"></i>' . $name;
    },
    'Tutor' => 'tutor',
    'Espécie' => 'especie',
    'Raça' => 'raca',
    'Nascimento' => static fn(array $row): string => e(date_br($row['data_nascimento'] ?? null)),
    'Idade' => static fn(array $row): string => e(age($row['data_nascimento'] ?? null)),
    'Status' => static fn(array $row): string => status_badge((int) ($row['ativo'] ?? 0) === 1 ? 'ativo' : 'inativo'),
    'Ações' => $actionCell,
];
$actions = [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
if ($especieId > 0) {
    $html = str_replace('&amp;page=', '&amp;especie_id=' . rawurlencode((string) $especieId) . '&amp;page=', $html);
}
echo $html;
