<?php

$title = $title ?? 'Nova conta a receber';
$fields = $fields ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'financeiro.store-receber';
$cancelRoute = $cancelRoute ?? 'financeiro.index';
$submitLabel = $submitLabel ?? 'Cadastrar conta';

require __DIR__ . '/../common/form.php';
