<?php
$title = $title ?? 'Meu perfil';
$values = $values ?? [];
$errors = $errors ?? [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Atualize seus dados de contato e endereço.</p>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="card-body p-4">
        <form method="post" action="<?= e(url('portal.update-perfil')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label fw-semibold">Nome</label>
                    <input type="text" class="form-control <?= isset($errors['nome']) ? 'is-invalid' : '' ?>" id="nome" name="nome" value="<?= e($values['nome'] ?? '') ?>" required>
                    <?php if (isset($errors['nome'])): ?><div class="invalid-feedback"><?= e($errors['nome']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">E-mail</label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($values['email'] ?? '') ?>">
                    <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4"><label for="telefone" class="form-label fw-semibold">Telefone</label><input type="text" class="form-control" id="telefone" name="telefone" value="<?= e($values['telefone'] ?? '') ?>"></div>
                <div class="col-md-4"><label for="whatsapp" class="form-label fw-semibold">WhatsApp</label><input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?= e($values['whatsapp'] ?? '') ?>"></div>
                <div class="col-md-4"><label for="cep" class="form-label fw-semibold">CEP</label><input type="text" class="form-control" id="cep" name="cep" value="<?= e($values['cep'] ?? '') ?>"></div>
                <div class="col-md-8"><label for="endereco" class="form-label fw-semibold">Endereço</label><input type="text" class="form-control" id="endereco" name="endereco" value="<?= e($values['endereco'] ?? '') ?>"></div>
                <div class="col-md-4"><label for="numero" class="form-label fw-semibold">Número</label><input type="text" class="form-control" id="numero" name="numero" value="<?= e($values['numero'] ?? '') ?>"></div>
                <div class="col-md-4"><label for="complemento" class="form-label fw-semibold">Complemento</label><input type="text" class="form-control" id="complemento" name="complemento" value="<?= e($values['complemento'] ?? '') ?>"></div>
                <div class="col-md-4"><label for="bairro" class="form-label fw-semibold">Bairro</label><input type="text" class="form-control" id="bairro" name="bairro" value="<?= e($values['bairro'] ?? '') ?>"></div>
                <div class="col-md-3"><label for="cidade" class="form-label fw-semibold">Cidade</label><input type="text" class="form-control" id="cidade" name="cidade" value="<?= e($values['cidade'] ?? '') ?>"></div>
                <div class="col-md-1"><label for="estado" class="form-label fw-semibold">UF</label><input type="text" class="form-control" id="estado" name="estado" maxlength="2" value="<?= e($values['estado'] ?? '') ?>"></div>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-save me-2"></i>Salvar perfil</button>
            </div>
        </form>
    </div>
</div>
