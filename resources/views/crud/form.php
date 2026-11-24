<?php

$config = $config ?? [];
$slug = $slug ?? '';
$title = $title ?? 'Registro';
$fields = $fields ?? ($config['fields'] ?? []);
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? '';
$cancelRoute = $cancelRoute ?? ((string) ($config['route'] ?? 'crud.' . $slug)) . '.index';
$submitLabel = $submitLabel ?? 'Salvar';
$enctype = null;

require __DIR__ . '/../common/form.php';
