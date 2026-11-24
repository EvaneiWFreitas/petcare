<?php

$title = $title ?? 'Exames';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'exames';
$createRoute = $createRoute ?? 'exames.create';
$search = $search ?? '';
$extraFilters = $extraFilters ?? '';
$paginator = $paginator ?? null;
$actions = $actions ?? [];

require __DIR__ . '/../common/list.php';
