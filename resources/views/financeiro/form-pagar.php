<?php

$title = $title ?? 'Nova conta a pagar';
$fields = $fields ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'financeiro.store-pagar';
$cancelRoute = $cancelRoute ?? 'financeiro.index';
$submitLabel = $submitLabel ?? 'Cadastrar conta';

require __DIR__ . '/../common/form.php';
