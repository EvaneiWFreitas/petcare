<?php

$title = $title ?? 'Vacinação';
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'vacinas.store';
$cancelRoute = $cancelRoute ?? 'vacinas.index';
$submitLabel = $submitLabel ?? 'Salvar';
$fields = $fields ?? [];

require __DIR__ . '/../common/form.php';
