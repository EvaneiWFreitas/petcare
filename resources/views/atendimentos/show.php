<?php

$title = $title ?? 'Detalhes do atendimento';
$sections = $sections ?? [];
$actions = $actions ?? [];
$recordId = $recordId ?? 0;
$atendimento = $atendimento ?? [];
$prontuario = $prontuario ?? null;
$receita = $receita ?? null;

ob_start();
require __DIR__ . '/../common/detail.php';
$html = (string) ob_get_clean();

$replacements = [];
if ($prontuario !== null) {
    $replacements['prontuarios.edit'] = ['id' => (int) $prontuario['id']];
} else {
    $replacements['prontuarios.create'] = ['atendimento_id' => (int) $recordId, 'animal_id' => (int) ($atendimento['animal_id'] ?? 0)];
}
if ($receita !== null) {
    $replacements['receitas.print'] = ['id' => (int) $receita['id']];
}
foreach ($replacements as $route => $params) {
    $from = 'href="' . e(url($route)) . '"';
    $to = 'href="' . e(url($route, $params)) . '"';
    $html = str_replace($from, $to, $html);
}

echo $html;
