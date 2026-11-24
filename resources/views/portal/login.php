<?php
$title = $title ?? 'Acesso do tutor';
$errors = $errors ?? [];
?>
<div class="row justify-content-center py-4">
    <div class="col-md-7 col-lg-5">
        <div class="card border-0 shadow-petcare">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="display-6 text-petcare mb-2"><i class="fa-solid fa-paw"></i></div>
                    <h1 class="h4 fw-bold mb-1">Portal do Tutor</h1>
                    <p class="text-muted small mb-0">Acesse com o e-mail e o CPF cadastrados na clínica.</p>
                </div>
                <form method="post" action="<?= e(url('portal.authenticate')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">E-mail</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e(old('email', '')) ?>" required autocomplete="email">
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="senha" class="form-label fw-semibold">CPF</label>
                        <input type="password" class="form-control <?= isset($errors['senha']) ? 'is-invalid' : '' ?>" id="senha" name="senha" required autocomplete="off" placeholder="Digite seu CPF">
                        <?php if (isset($errors['senha'])): ?><div class="invalid-feedback"><?= e($errors['senha']) ?></div><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-petcare w-100"><i class="fa-solid fa-right-to-bracket me-2"></i>Entrar</button>
                </form>
                <div class="text-muted small mt-3">O acesso usa o e-mail e o CPF cadastrados. Em caso de dúvida, contate a clínica.</div>
            </div>
        </div>
    </div>
</div>
<?php clear_old(); ?>
