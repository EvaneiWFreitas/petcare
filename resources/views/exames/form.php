<?php

$title = $title ?? 'Solicitar exame';
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'exames.store';
$cancelRoute = $cancelRoute ?? 'exames.index';
$submitLabel = $submitLabel ?? 'Salvar';
$enctype = $enctype ?? null;
$fields = $fields ?? [];

require __DIR__ . '/../common/form.php';
