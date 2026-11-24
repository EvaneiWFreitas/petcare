<?php
$title = $title ?? 'Usuário';
$values = $values ?? [];
$errors = $errors ?? [];
$perfis = $perfis ?? [];
$action = $action ?? 'usuarios.store';
$isEdit = !empty($isEdit);
$enctype = $enctype ?? '';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Preencha os dados do usuário.</p>
    </div>
    <a href="<?= e(url('usuarios.index')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
</div>

<div class="card border-0 shadow-petcare">
    <div class="card-body p-4">
        <form method="post" action="<?= e(url($action)) ?>" <?= $enctype !== '' ? 'enctype="' . e($enctype) . '"' : '' ?> novalidate>
            <?= csrf_field() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= e($values['id'] ?? '') ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label fw-semibold">Nome</label>
                    <input type="text" class="form-control <?= isset($errors['nome']) ? 'is-invalid' : '' ?>" id="nome" name="nome" value="<?= e($values['nome'] ?? '') ?>" required>
                    <?php if (isset($errors['nome'])): ?><div class="invalid-feedback"><?= e($errors['nome']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">E-mail</label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($values['email'] ?? '') ?>" required>
                    <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label for="foto" class="form-label fw-semibold">Foto do usuário</label>
                    <?php if ($isEdit && !empty($values['foto'])): ?>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="<?= e(upload_url((string) $values['foto'])) ?>" alt="Foto atual de <?= e($values['nome'] ?? '') ?>" class="user-avatar rounded-circle border shadow-sm">
                            <div>
                                <div class="small text-muted mb-1">Foto atual</div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="remover_foto" name="remover_foto" <?= checked($values['remover_foto'] ?? 0, 1) ?>>
                                    <label class="form-check-label small" for="remover_foto">Remover foto atual</label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>" id="foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">JPG, PNG, WEBP ou GIF. A imagem será exibida no cabeçalho do sistema.</div>
                    <?php if (isset($errors['foto'])): ?><div class="invalid-feedback d-block"><?= e($errors['foto']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="perfil_id" class="form-label fw-semibold">Perfil</label>
                    <select class="form-select <?= isset($errors['perfil_id']) ? 'is-invalid' : '' ?>" id="perfil_id" name="perfil_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($perfis as $perfil): ?>
                            <option value="<?= e($perfil['id']) ?>" <?= selected($values['perfil_id'] ?? '', $perfil['id']) ?>><?= e($perfil['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['perfil_id'])): ?><div class="invalid-feedback"><?= e($errors['perfil_id']) ?></div><?php endif; ?>
                </div>
                <?php if (!$isEdit): ?>
                    <div class="col-md-6">
                        <label for="senha" class="form-label fw-semibold">Senha</label>
                        <input type="password" class="form-control <?= isset($errors['senha']) ? 'is-invalid' : '' ?>" id="senha" name="senha" autocomplete="new-password" required>
                        <div class="form-text">Mínimo de 8 caracteres.</div>
                        <?php if (isset($errors['senha'])): ?><div class="invalid-feedback"><?= e($errors['senha']) ?></div><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="col-md-6">
                        <label for="nova_senha" class="form-label fw-semibold">Nova senha</label>
                        <input type="password" class="form-control <?= isset($errors['nova_senha']) ? 'is-invalid' : '' ?>" id="nova_senha" name="nova_senha" autocomplete="new-password">
                        <div class="form-text">Preencha apenas se desejar alterar a senha (mínimo 8 caracteres).</div>
                        <?php if (isset($errors['nova_senha'])): ?><div class="invalid-feedback"><?= e($errors['nova_senha']) ?></div><?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="col-12">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" id="ativo" name="ativo" <?= checked($values['ativo'] ?? 0, 1) ?>>
                        <label class="form-check-label" for="ativo">Usuário ativo</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="trocar_senha" name="trocar_senha" <?= checked($values['trocar_senha'] ?? 0, 1) ?>>
                        <label class="form-check-label" for="trocar_senha">Exigir troca de senha no próximo acesso</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= e(url('usuarios.index')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-save me-2"></i>Salvar</button>
            </div>
        </form>
    </div>
</div>
