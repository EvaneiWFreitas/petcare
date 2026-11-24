<?php

$title = $title ?? 'Nova cirurgia';
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'cirurgias.store';
$cancelRoute = $cancelRoute ?? 'cirurgias.index';
$submitLabel = $submitLabel ?? 'Salvar';
$fields = $fields ?? [];

require __DIR__ . '/../common/form.php';
