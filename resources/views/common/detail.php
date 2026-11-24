<?php

/** @var string $title */
/** @var array $sections */
/** @var array $actions */

$title = $title ?? 'Detalhes';
$sections = $sections ?? [];
$actions = $actions ?? [];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h3 mb-0 fw-bold text-petcare"><?= e($title) ?></h1>
    <div class="d-flex gap-2 flex-wrap">
        <?php foreach ($actions as $action): ?>
            <?php
            $href = str_replace(':id', (string) ($recordId ?? ''), $action['route']);
            $method = $action['method'] ?? 'get';
            $class = $action['class'] ?? 'btn-outline-secondary';
            $icon = $action['icon'] ?? 'arrow-left';
            $label = $action['label'] ?? 'Ação';
            ?>
            <?php if ($method === 'post'): ?>
                <form method="post" action="<?= e(url($href)) ?>" onsubmit="return confirm('<?= e($action['confirm'] ?? 'Confirma esta ação?') ?>')">
                    <?= csrf_field() ?>
                    <button class="btn <?= e($class) ?>"><i class="fa-solid fa-<?= e($icon) ?> me-1"></i><?= e($label) ?></button>
                </form>
            <?php else: ?>
                <a href="<?= e(url($href)) ?>" class="btn <?= e($class) ?>"><i class="fa-solid fa-<?= e($icon) ?> me-1"></i><?= e($label) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($sections as $sectionTitle => $rows): ?>
        <div class="col-lg-6">
            <div class="card border-0 shadow-petcare h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-circle-info me-2"></i><?= e($sectionTitle) ?></h2>
                </div>
                <div class="card-body p-3 p-md-4">
                    <dl class="row mb-0">
                        <?php foreach ($rows as $label => $value): ?>
                            <dt class="col-sm-5 small text-muted fw-semibold"><?= e($label) ?></dt>
                            <dd class="col-sm-7 small mb-2">
                                <?php
                                if (is_callable($value)) {
                                    echo $value();
                                } elseif ($value === null || $value === '') {
                                    echo '<span class="text-muted">—</span>';
                                } else {
                                    echo e($value);
                                }
                                ?>
                            </dd>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
