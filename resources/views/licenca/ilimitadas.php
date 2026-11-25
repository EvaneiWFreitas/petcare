<?php

include __DIR__ . '/_nav.php';

$modals = '';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare">Licenças ilimitadas</h1>
        <p class="text-muted mb-0 small">Ative ou desative a licença sem vencimento. Ações irreversíveis exigem confirmação.</p>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Clínica</th>
                    <th class="small text-uppercase text-muted fw-bold">Licença</th>
                    <th class="small text-uppercase text-muted fw-bold">Situação</th>
                    <th class="small text-uppercase text-muted fw-bold">Vencimento</th>
                    <th class="text-end small text-uppercase text-muted fw-bold">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($licencas === []): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Nenhuma licença cadastrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($licencas as $row): ?>
                        <?php $id = (int) ($row['id'] ?? 0); ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($row['clinica_nome'] ?? '—') ?></div>
                                <div class="small text-muted"><?= e($row['clinica_codigo'] ?? '—') ?></div>
                            </td>
                            <td><?= e($row['codigo_licenca'] ?? '—') ?></td>
                            <td><?= license_badge($row['status_efetivo'] ?? null) ?></td>
                            <td><?= !empty($row['permanente']) ? '<span class="text-muted">Sem vencimento</span>' : e(date_br($row['data_vencimento'] ?? null)) ?></td>
                            <td class="text-end">
                                <?php if (!empty($row['permanente'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#desativarPermanente<?= $id ?>">
                                        <i class="fa-solid fa-rotate-left me-1"></i>Voltar com vencimento
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ativarPermanente<?= $id ?>">
                                        <i class="fa-solid fa-infinity me-1"></i>Ativar ilimitada
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php ob_start(); ?>
                        <div class="modal fade" id="ativarPermanente<?= $id ?>" tabindex="-1" aria-labelledby="ativarPermanenteLabel<?= $id ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="post" action="<?= e(url('licenca.ativar-permanente')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="clinica_id" value="<?= $id ?>">
                                        <div class="modal-header">
                                            <h2 class="modal-title h6 fw-bold" id="ativarPermanenteLabel<?= $id ?>">Ativar licença ilimitada</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body small">
                                            A clínica <strong><?= e($row['clinica_nome'] ?? '—') ?></strong> deixará de ser bloqueada por vencimento. Deseja continuar?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-petcare">Confirmar ativação</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="desativarPermanente<?= $id ?>" tabindex="-1" aria-labelledby="desativarPermanenteLabel<?= $id ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="post" action="<?= e(url('licenca.desativar-permanente')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="clinica_id" value="<?= $id ?>">
                                        <div class="modal-header">
                                            <h2 class="modal-title h6 fw-bold" id="desativarPermanenteLabel<?= $id ?>">Voltar a licença com vencimento</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="small mb-3">A clínica voltará a ser bloqueada quando o prazo terminar. Informe quantos dias deseja conceder.</p>
                                            <label for="dias_desativar<?= $id ?>" class="form-label small fw-semibold">Dias</label>
                                            <input type="number" min="1" max="3650" name="dias" id="dias_desativar<?= $id ?>" class="form-control" value="30" required>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-warning">Confirmar desativação</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php $modals .= (string) ob_get_clean(); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $modals ?>
