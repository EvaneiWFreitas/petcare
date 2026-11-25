<?php

use PetCare\Core\Database;

$title = $title ?? 'Clientes';
$route = 'clientes';
$createRoute = 'clientes.create';
$rows = $rows ?? [];
$search = $search ?? '';
$situacao = $situacao ?? 'ativo';
$paginator = $paginator ?? null;
$extraFilters = $extraFilters ?? '';
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
    'Código' => 'codigo',
    'Nome' => static function (array $row): string {
        $nome = e($row['nome'] ?? '—');
        if (!empty($row['foto'])) {
            return '<span class="d-flex align-items-center gap-2"><img src="' . e(upload_url($row['foto'])) . '" alt="" class="rounded-circle object-fit-cover border" width="36" height="36"><span>' . $nome . '</span></span>';
        }
        $initial = e(mb_strtoupper(mb_substr((string) ($row['nome'] ?? '?'), 0, 1)));
        return '<span class="d-flex align-items-center gap-2"><span class="rounded-circle bg-petcare-soft d-flex align-items-center justify-content-center text-petcare fw-bold" style="width:36px;height:36px;">' . $initial . '</span><span>' . $nome . '</span></span>';
    },
    'CPF' => static function (array $row): string {
        return e(mask_cpf($row['cpf'] ?? ''));
    },
    'Telefone' => 'telefone',
    'E-mail' => 'email',
    'Pets' => static function (array $row): string {
        $count = (int) Database::scalar('SELECT COUNT(*) FROM animais WHERE cliente_id = :id', ['id' => (int) ($row['id'] ?? 0)]);
        return '<span class="badge bg-petcare-soft rounded-pill">' . $count . '</span>';
    },
    'Situação' => static function (array $row): string {
        return status_badge($row['status'] ?? null);
    },
    'Ações' => $actionCell,
];
$actions = [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
if ($situacao !== '') {
    $html = str_replace('&amp;page=', '&amp;situacao=' . rawurlencode((string) $situacao) . '&amp;page=', $html);
}
echo $html;
