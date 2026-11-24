<?php

$title = $title ?? 'Produto';
$fields = $fields ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? 'estoque.store';
$cancelRoute = $cancelRoute ?? 'estoque.index';
$submitLabel = $submitLabel ?? 'Cadastrar produto';

require __DIR__ . '/../common/form.php';
