<?php

$title = $title ?? 'Internações';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'internacoes';
$createRoute = $createRoute ?? 'internacoes.create';
$search = $search ?? '';
$paginator = $paginator ?? null;
$actions = $actions ?? [];

require __DIR__ . '/../common/list.php';
