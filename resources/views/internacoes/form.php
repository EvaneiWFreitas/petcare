<?php

$title = $title ?? 'Nova internação';
$values = $values ?? [];
if (!empty($values['data_entrada']) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', (string) $values['data_entrada']) === 1) {
    $values['data_entrada'] = str_replace(' ', 'T', substr((string) $values['data_entrada'], 0, 16));
}
$errors = $errors ?? [];
$action = $action ?? 'internacoes.store';
$cancelRoute = $cancelRoute ?? 'internacoes.index';
$submitLabel = $submitLabel ?? 'Salvar';
$fields = $fields ?? [];

ob_start();
require __DIR__ . '/../common/form.php';
$html = (string) ob_get_clean();
$html = preg_replace('/<input type="text"(\s+)name="data_entrada"/', '<input type="datetime-local"$1name="data_entrada"', $html, 1) ?? $html;
echo $html;
