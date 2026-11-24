<?php

$title = $title ?? 'Contas a pagar';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'financeiro.pagar';
$createRoute = $createRoute ?? 'financeiro.create-pagar';
$search = $search ?? '';
$status = $status ?? '';
$extraFilters = $extraFilters ?? '';
$paginator = $paginator ?? null;
$actions = $actions ?? [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
$html = str_replace('Novo registro', 'Nova conta a pagar', $html);
$html = str_replace('financeiro.pagar.index', 'financeiro.pagar', $html);
if ($status !== '') {
    $html = str_replace('&amp;page=', '&amp;status=' . rawurlencode((string) $status) . '&amp;page=', $html);
}
echo $html;
