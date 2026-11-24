<?php

$title = $title ?? 'Receitas';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'receitas';
$createRoute = $createRoute ?? 'receitas.create';
$search = $search ?? '';
$paginator = $paginator ?? null;
$actions = $actions ?? [];

require __DIR__ . '/../common/list.php';
