<?php
$title = $title ?? 'Usuários';
$rows = $rows ?? [];
$search = $search ?? '';
$paginator = $paginator ?? null;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Gerencie usuários e permissões do sistema.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (can('usuarios', 'approve')): ?>
            <a href="<?= e(url('usuarios.permissions')) ?>" class="btn btn-outline-petcare"><i class="fa-solid fa-key me-2"></i>Permissões</a>
        <?php endif; ?>
        <?php if (can('usuarios', 'create')): ?>
            <a href="<?= e(url('usuarios.create')) ?>" class="btn btn-petcare shadow-sm"><i class="fa-solid fa-plus me-2"></i>Novo usuário</a>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="get" action="<?= e(url('usuarios.index')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="r" value="usuarios.index">
            <div class="col-md-6 col-lg-5">
                <label for="q" class="form-label small fw-semibold">Pesquisar</label>
                <input type="search" name="q" id="q" class="form-control" value="<?= e($search) ?>" placeholder="Nome, e-mail ou perfil">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-petcare"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</button>
            </div>
            <?php if ($search !== ''): ?>
                <div class="col-auto"><a href="<?= e(url('usuarios.index')) ?>" class="btn btn-link text-decoration-none">Limpar</a></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Nome</th>
                    <th class="small text-uppercase text-muted fw-bold">E-mail</th>
                    <th class="small text-uppercase text-muted fw-bold">Perfil</th>
                    <th class="small text-uppercase text-muted fw-bold">Situação</th>
                    <th class="small text-uppercase text-muted fw-bold">Último acesso</th>
                    <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fa-regular fa-folder-open fa-2x d-block mb-2"></i>Nenhum usuário encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="fw-semibold">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($row['foto'])): ?>
                                        <img src="<?= e(upload_url((string) $row['foto'])) ?>" alt="<?= e($row['nome'] ?? '') ?>" class="user-avatar-sm rounded-circle border">
                                    <?php else: ?>
                                        <span class="user-avatar-fallback-sm rounded-circle bg-petcare-soft d-flex align-items-center justify-content-center text-petcare small"><?= e(mb_strtoupper(mb_substr((string) ($row['nome'] ?? '?'), 0, 1))) ?></span>
                                    <?php endif; ?>
                                    <span><?= e($row['nome'] ?? '') ?></span>
                                </div>
                            </td>
                            <td><?= e($row['email'] ?? '') ?></td>
                            <td><?= e($row['perfil'] ?? '') ?></td>
                            <td><?= status_badge($row['status'] ?? null) ?></td>
                            <td><?= e($row['ultimo_acesso'] ?? '—') ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    <?php if (can('usuarios', 'edit')): ?>
                                        <a href="<?= e(url('usuarios.edit', ['id' => $row['id']])) ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                    <?php endif; ?>
                                    <?php if (can('usuarios', 'approve')): ?>
                                        <a href="<?= e(url('usuarios.permissions')) ?>" class="btn btn-sm btn-outline-primary" title="Permissões"><i class="fa-solid fa-key"></i></a>
                                    <?php endif; ?>
                                    <?php if (can('usuarios', 'delete') && (int) ($row['id'] ?? 0) !== (int) (user()['id'] ?? 0)): ?>
                                        <?= render_action('usuarios.destroy', ['id' => $row['id']], ['label' => 'Excluir', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Excluir este usuário? Ele será inativado e perderá o acesso ao sistema.']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($paginator)): ?>
        <div class="card-footer bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">Exibindo <?= (int) $paginator['total'] ?> registro(s) — página <?= (int) $paginator['page'] ?> de <?= (int) $paginator['pages'] ?></small>
            <?= pagination($paginator, 'usuarios.index', array_filter(['q' => $search])) ?>
        </div>
    <?php endif; ?>
</div>
