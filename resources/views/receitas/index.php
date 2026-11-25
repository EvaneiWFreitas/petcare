<?php

$title = $title ?? 'Receitas';
$columns = $columns ?? [];
$rows = $rows ?? [];
$route = $route ?? 'receitas';
$createRoute = $createRoute ?? 'receitas.create';
$search = $search ?? '';
$paginator = $paginator ?? null;
$actions = $actions ?? [];

ob_start();
require __DIR__ . '/../common/list.php';
$html = (string) ob_get_clean();
$html = str_replace('Gerencie os registros do sistema PetCare.', 'Gerencie as receitas emitidas pelo sistema PetCare.', $html);
$html = str_replace('placeholder="Digite o termo da busca"', 'placeholder="Buscar por código, pet, tutor, veterinário ou medicamento"', $html);
echo $html;
