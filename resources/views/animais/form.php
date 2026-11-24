<?php

$title = $title ?? 'Animal';
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'animais.store';
$cancelRoute = $cancelRoute ?? 'animais.index';
$cancelParams = $cancelParams ?? [];
$submitLabel = $submitLabel ?? 'Salvar';
$enctype = $enctype ?? 'multipart/form-data';
$fields = $fields ?? [];

if (!empty($values['id'])) {
    $fields = ['id' => ['label' => 'ID', 'type' => 'text', 'rules' => '', 'col' => 'd-none']] + $fields;
}

ob_start();
require __DIR__ . '/../common/form.php';
$html = (string) ob_get_clean();

if ($cancelParams !== []) {
    $original = e(url((string) $cancelRoute));
    $target = e(url((string) $cancelRoute, $cancelParams));
    $html = str_replace('href="' . $original . '"', 'href="' . $target . '"', $html);
}

echo $html;
