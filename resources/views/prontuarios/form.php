<?php

$title = $title ?? 'Prontuário';
$fields = $fields ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'prontuarios.store';
$cancelaRoute = $cancelRoute ?? 'prontuarios.index';
$submitLabel = $submitLabel ?? 'Salvar';
$enctype = $enctype ?? null;
$warning = $warning ?? null;

ob_start();
require __DIR__ . '/../common/form.php';
$html = (string) ob_get_clean();
$html = str_replace(
    '<input type="text" name="field_data_atendimento" id="field_data_atendimento"',
    '<input type="datetime-local" name="field_data_atendimento" id="field_data_atendimento"',
    $html
);
if ($action === 'prontuarios.update' && (int) ($values['id'] ?? 0) > 0) {
    $from = 'href="' . e(url('prontuarios.show')) . '"';
    $to = 'href="' . e(url('prontuarios.show', ['id' => (int) $values['id']])) . '"';
    $html = str_replace($from, $to, $html);
}

$alert = '';
if ($warning !== null && $warning !== '') {
    $alert = '<div class="alert alert-warning border-0 shadow-sm mb-4"><i class="fa-solid fa-shield-halved me-2"></i>' . e($warning) . '</div>';
}
echo $alert . $html;
