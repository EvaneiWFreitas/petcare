<?php

$title = $title ?? 'Cirurgias';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'cirurgias';
$createRoute = $createRoute ?? 'cirurgias.create';
$search = $search ?? '';
$paginator = $paginator ?? null;
$actions = $actions ?? [];

require __DIR__ . '/../common/list.php';
