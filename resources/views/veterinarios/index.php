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
        $html .= render_action((string) ($action['route'] ?? ''), ['id' => $id], $action);
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
