<?php
$title = $title ?? 'Permissões';
$perfis = $perfis ?? [];
$perfil = $perfil ?? [];
$modules = $modules ?? [];
$actions = $actions ?? [];
$granted = $granted ?? [];
$moduleLabels = [
    'dashboard' => 'Dashboard', 'clientes' => 'Clientes', 'animais' => 'Animais', 'agenda' => 'Agenda', 'atendimentos' => 'Atendimentos',
    'prontuarios' => 'Prontuários', 'vacinas' => 'Vacinas', 'receitas' => 'Receitas', 'exames' => 'Exames', 'cirurgias' => 'Cirurgias',
    'internacoes' => 'Internações', 'farmacia' => 'Farmácia/Estoque', 'financeiro' => 'Financeiro', 'compras' => 'Compras', 'banho_tosa' => 'Banho e tosa',
    'usuarios' => 'Usuários', 'relatorios' => 'Relatórios', 'notificacoes' => 'Notificações', 'configuracoes' => 'Configurações', 'portal' => 'Portal',
];
$actionLabels = ['view' => 'Visualizar', 'create' => 'Criar', 'edit' => 'Editar', 'delete' => 'Excluir', 'print' => 'Imprimir', 'approve' => 'Aprovar'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Configure as permissões por perfil de acesso.</p>
    </div>
    <a href="<?= e(url('usuarios.index')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Voltar</a>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= e(url('usuarios.permissions')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="r" value="usuarios.permissions">
            <div class="col-md-6 col-lg-4">
                <label for="perfil_id" class="form-label fw-semibold">Perfil</label>
                <select name="perfil_id" id="perfil_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($perfis as $p): ?>
                        <option value="<?= e($p['id']) ?>" <?= (int) ($perfil['id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <noscript><button type="submit" class="btn btn-outline-petcare">Carregar</button></noscript>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="card-body p-0">
        <form method="post" action="<?= e(url('usuarios.save-permissions', ['perfil_id' => $perfil['id'] ?? 0])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="perfil_id" value="<?= e($perfil['id'] ?? 0) ?>">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Módulo</th>
                            <?php foreach ($actions as $action): ?>
                                <th class="text-center small text-uppercase text-muted fw-bold"><?= e($actionLabels[$action] ?? $action) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($moduleLabels[$module] ?? $module) ?></td>
                                <?php foreach ($actions as $action): ?>
                                    <?php $checkedPerm = !empty($granted[$module][$action]); ?>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="permissao[<?= e($module) ?>][<?= e($action) ?>]" value="1" <?= $checkedPerm ? 'checked' : '' ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end gap-2 p-4 border-top">
                <a href="<?= e(url('usuarios.index')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-save me-2"></i>Salvar permissões</button>
            </div>
        </form>
    </div>
</div>
