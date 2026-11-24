<?php

$title = $title ?? 'Compras';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'compras';
$createRoute = $createRoute ?? 'compras.create';
$search = $search ?? '';
$paginator = $paginator ?? null;
$actions = $actions ?? [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
echo str_replace('Novo registro', 'Nova compra', $html);
