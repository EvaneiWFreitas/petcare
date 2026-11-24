<?php

/** @var array $fields */
/** @var array $values */
/** @var array $errors */
/** @var string $action */
/** @var string $title */

use PetCare\Core\Database;

$fields = $fields ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$action = $action ?? '';
$title = $title ?? 'Formulário';
$cancelRoute = $cancelRoute ?? null;
$submitLabel = $submitLabel ?? 'Salvar';
$enctype = $enctype ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Preencha os campos abaixo e salve para continuar.</p>
    </div>
    <?php if ($cancelRoute): ?>
        <a href="<?= e(url($cancelRoute)) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
    <?php endif; ?>
</div>

<form method="post" action="<?= e(url($action)) ?>" <?= $enctype ? 'enctype="' . e($enctype) . '"' : '' ?> novalidate>
    <?= csrf_field() ?>
    <div class="card border-0 shadow-petcare">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <?php foreach ($fields as $name => $field): ?>
                    <?php
                    $type = $field['type'] ?? 'text';
                    $label = $field['label'] ?? ucfirst(str_replace('_', ' ', $name));
                    $col = $field['col'] ?? 'col-md-6';
                    $value = $values[$name] ?? ($field['default'] ?? '');
                    $placeholder = $field['placeholder'] ?? '';
                    $required = !empty($field['required']) || in_array('required', explode('|', (string) ($field['rules'] ?? '')), true);
                    $error = $errors[$name] ?? null;
                    $options = $field['options'] ?? [];
                    if (!empty($field['options_query'])) {
                        foreach (Database::fetchAll((string) $field['options_query']) as $option) {
                            $options[$option['id']] = $option['nome'];
                        }
                    }
                    ?>
                    <div class="<?= e($col) ?>">
                        <label for="field_<?= e($name) ?>" class="form-label small fw-semibold">
                            <?= e($label) ?> <?= $required ? '<span class="text-danger">*</span>' : '' ?>
                        </label>

                        <?php if ($type === 'select'): ?>
                            <select name="<?= e($name) ?>" id="field_<?= e($name) ?>" class="form-select <?= $error ? 'is-invalid' : '' ?>">
                                <option value=""><?= e($field['empty'] ?? 'Selecione...') ?></option>
                                <?php foreach ($options as $optionValue => $optionLabel): ?>
                                    <option value="<?= e($optionValue) ?>" <?= selected($value, $optionValue) ?>><?= e($optionLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'textarea'): ?>
                            <textarea name="<?= e($name) ?>" id="field_<?= e($name) ?>" rows="<?= (int) ($field['rows'] ?? 3) ?>" class="form-control <?= $error ? 'is-invalid' : '' ?>" placeholder="<?= e($placeholder) ?>"><?= e($value) ?></textarea>
                        <?php elseif ($type === 'checkbox'): ?>
                            <input type="hidden" name="<?= e($name) ?>" value="0">
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" name="<?= e($name) ?>" value="1" id="field_<?= e($name) ?>" class="form-check-input" <?= checked($value, 1) ?>>
                                <label class="form-check-label small" for="field_<?= e($name) ?>">Sim</label>
                            </div>
                        <?php elseif ($type === 'file'): ?>
                            <input type="file" name="<?= e($name) ?>" id="field_<?= e($name) ?>" class="form-control <?= $error ? 'is-invalid' : '' ?>" accept="<?= e($field['accept'] ?? 'image/*') ?>">
                            <?php if (!empty($value)): ?>
                                <small class="text-muted d-block mt-1">Arquivo atual: <a href="<?= e(asset($value)) ?>" target="_blank" rel="noopener">visualizar</a></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <input type="<?= e($type === 'password' ? 'password' : ($type === 'number' ? 'number' : ($type === 'date' ? 'date' : ($type === 'time' ? 'time' : ($type === 'email' ? 'email' : 'text'))))) ?>"
                                   name="<?= e($name) ?>" id="field_<?= e($name) ?>"
                                   class="form-control <?= $error ? 'is-invalid' : '' ?>"
                                   value="<?= e($value) ?>"
                                   placeholder="<?= e($placeholder) ?>"
                                   <?= $type === 'number' ? 'step="any"' : '' ?>>
                        <?php endif; ?>

                        <?php if ($error): ?><div class="invalid-feedback d-block small"><?= e($error) ?></div><?php endif; ?>
                        <?php if (!empty($field['help'])): ?><small class="text-muted d-block mt-1"><?= e($field['help']) ?></small><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer bg-white border-0 d-flex justify-content-end gap-2 p-3 p-md-4">
            <?php if ($cancelRoute): ?>
                <a href="<?= e(url($cancelRoute)) ?>" class="btn btn-light">Cancelar</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-floppy-disk me-2"></i><?= e($submitLabel) ?></button>
        </div>
    </div>
</form>
