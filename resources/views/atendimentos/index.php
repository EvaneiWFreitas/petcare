<?php

$title = $title ?? 'Atendimentos';
$route = 'atendimentos';
$createRoute = can('atendimentos', 'create') ? 'atendimentos.create' : null;
$rows = $rows ?? [];
$search = $search ?? '';
$status = $status ?? '';
$paginator = $paginator ?? null;
$actionDefs = $actions ?? [];

$actionCell = static function (array $row) use ($actionDefs): string {
    $html = '<div class="d-flex justify-content-end gap-1 flex-wrap">';
    foreach ($actionDefs as $action) {
        if (!empty($action['prontuario']) && (int) ($row['prontuario_id'] ?? 0) <= 0) {
            continue;
        }
        if (!empty($action['prontuario'])) {
            $html .= render_action('prontuarios.show', ['prontuario_id' => (int) $row['prontuario_id'], 'id' => (int) $row['prontuario_id']], $action);
        } else {
            $html .= render_action((string) ($action['route'] ?? ''), ['id' => (int) ($row['id'] ?? 0)], $action);
        }
    }
    return $html . '</div>';
};

$columns = [
    'Código' => 'codigo',
    'Pet' => static fn(array $row): string => e($row['pet'] ?? ''),
    'Tutor' => static fn(array $row): string => e($row['tutor'] ?? ''),
    'Veterinário' => static fn(array $row): string => e($row['veterinario'] ?? ''),
    'Data' => static fn(array $row): string => e(date_br((string) ($row['data_atendimento'] ?? ''), true)),
    'Queixa' => static fn(array $row): string => e(mb_strimwidth((string) ($row['queixa_principal'] ?? ''), 0, 60, '...')),
    'Valor' => static fn(array $row): string => e(money($row['valor'] ?? 0)),
    'Status' => 'status',
    'Ações' => $actionCell,
];
$actions = [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
$html = str_replace('Novo registro', 'Novo atendimento', $html);
if ($status !== '') {
    $html = str_replace('&amp;page=', '&amp;status=' . rawurlencode($status) . '&amp;page=', $html);
}
echo $html;
