<?php

$title = $title ?? 'Contas a receber';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'financeiro.receber';
$createRoute = $createRoute ?? 'financeiro.create-receber';
$search = $search ?? '';
$status = $status ?? '';
$extraFilters = $extraFilters ?? '';
$paginator = $paginator ?? null;
$actions = $actions ?? [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
$html = str_replace('Novo registro', 'Nova conta a receber', $html);
$html = str_replace('financeiro.receber.index', 'financeiro.receber', $html);
if ($status !== '') {
    $html = str_replace('&amp;page=', '&amp;status=' . rawurlencode((string) $status) . '&amp;page=', $html);
}
echo $html;
