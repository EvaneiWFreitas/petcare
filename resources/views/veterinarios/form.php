<?php

$title = $title ?? 'Veterinário';
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'veterinarios.store';
$cancelRoute = $cancelRoute ?? 'veterinarios.index';
$submitLabel = $submitLabel ?? 'Salvar';
$enctype = null;
$fields = $fields ?? [];

if (!empty($values['id'])) {
    $fields = ['id' => ['label' => 'ID', 'type' => 'text', 'rules' => '', 'col' => 'd-none']] + $fields;
}

require __DIR__ . '/../common/form.php';
