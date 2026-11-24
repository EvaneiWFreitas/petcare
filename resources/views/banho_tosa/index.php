<?php

$title = $title ?? 'Banho e tosa';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'banho_tosa';
$createRoute = $createRoute ?? 'banho_tosa.create';
$search = $search ?? '';
$paginator = $paginator ?? null;
$actions = $actions ?? [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
echo str_replace('Novo registro', 'Novo agendamento', $html);
