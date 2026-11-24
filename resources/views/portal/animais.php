<?php
$title = $title ?? 'Meus animais';
$rows = $rows ?? [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Animais vinculados ao seu cadastro.</p>
    </div>
</div>

<?php if ($rows === []): ?>
    <div class="card border-0 shadow-petcare"><div class="card-body text-center py-5 text-muted"><i class="fa-solid fa-paw fa-2x d-block mb-2"></i>Nenhum animal encontrado.</div></div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($rows as $row): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card border-0 shadow-petcare h-100">
                    <?php if (!empty($row['foto'])): ?>
                        <img src="<?= e(upload_url((string) $row['foto'])) ?>" class="card-img-top" alt="<?= e($row['nome'] ?? 'Animal') ?>" style="height:200px;object-fit:cover;" loading="lazy">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height:200px;"><i class="fa-solid fa-paw fa-3x text-muted"></i></div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="h5 mb-1"><?= e($row['nome'] ?? '') ?></h2>
                        <div class="text-muted small mb-2"><?= e($row['especie'] ?? 'Espécie não informada') ?><?php if (!empty($row['raca'])): ?> · <?= e($row['raca']) ?><?php endif; ?></div>
                        <div class="small mb-3">
                            <?php if (!empty($row['data_nascimento'])): ?><div>Idade: <?= e(age((string) $row['data_nascimento'])) ?></div><?php endif; ?>
                            <?php if (!empty($row['peso'])): ?><div>Peso: <?= e($row['peso']) ?> kg</div><?php endif; ?>
                        </div>
                        <a href="<?= e(url('portal.vacinas', ['animal_id' => $row['id']])) ?>" class="btn btn-sm btn-outline-petcare"><i class="fa-solid fa-syringe me-1"></i>Vacinas</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
