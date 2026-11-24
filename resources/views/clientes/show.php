<?php

$title = $title ?? 'Detalhes do cliente';
$cliente = $cliente ?? [];
$sections = $sections ?? [];
$pets = $pets ?? [];
$atendimentos = $atendimentos ?? [];
$contas = $contas ?? [];
$actions = $actions ?? [];
$recordId = $recordId ?? ($cliente['id'] ?? 0);
$novoPetUrl = url('animais.create', ['cliente_id' => (int) ($cliente['id'] ?? 0)]);
$contasAbertas = array_values(array_filter($contas, static fn(array $conta): bool => !in_array($conta['status'] ?? '', ['pago', 'cancelado'], true)));
$totalAberto = 0.0;
foreach ($contasAbertas as $conta) {
    $totalAberto += (float) ($conta['valor'] ?? 0) - (float) ($conta['valor_pago'] ?? 0);
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($cliente['nome'] ?? 'Cliente') ?></h1>
        <p class="text-muted mb-0 small">
            Código <?= e($cliente['codigo'] ?? '—') ?>
            <?php if (!empty($cliente['cpf'])): ?> · CPF <?= e(mask_cpf($cliente['cpf'])) ?><?php endif; ?>
            <?php if (!empty($cliente['telefone'])): ?> · <?= e($cliente['telefone']) ?><?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e($novoPetUrl) ?>" class="btn btn-petcare shadow-sm"><i class="fa-solid fa-paw me-2"></i>Novo pet</a>
        <?php foreach ($actions as $action): ?>
            <a href="<?= e($action['url'] ?? '') ?>" class="btn <?= e($action['class'] ?? 'btn-outline-secondary') ?>">
                <i class="fa-solid fa-<?= e($action['icon'] ?? 'arrow-right') ?> me-1"></i><?= e($action['label'] ?? 'Ação') ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-4 mb-4">
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
                                <?php if (is_callable($value)): ?>
                                    <?= $value() ?>
                                <?php elseif ($value === null || $value === ''): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <?= e($value) ?>
                                <?php endif; ?>
                            </dd>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
        <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-paw me-2"></i>Pets</h2>
        <span class="badge bg-petcare-soft rounded-pill"><?= count($pets) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase text-muted fw-bold">Nome</th>
                    <th class="small text-uppercase text-muted fw-bold">Foto</th>
                    <th class="small text-uppercase text-muted fw-bold">Espécie</th>
                    <th class="small text-uppercase text-muted fw-bold">Raça</th>
                    <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pets === []): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum pet cadastrado para este cliente.</td></tr>
                <?php else: ?>
                    <?php foreach ($pets as $pet): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($pet['nome'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($pet['foto'])): ?>
                                    <img src="<?= e(upload_url($pet['foto'])) ?>" alt="<?= e($pet['nome'] ?? 'Pet') ?>" width="36" height="36" class="rounded object-fit-cover">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fa-solid fa-paw"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($pet['especie'] ?? '') ?></td>
                            <td><?= e($pet['raca'] ?? '') ?></td>
                            <td class="text-end">
                                <a href="<?= e(url('animais.show', ['id' => $pet['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ver ficha"><i class="fa-solid fa-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3">
                <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-stethoscope me-2"></i>Atendimentos recentes</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Data</th>
                            <th class="small text-uppercase text-muted fw-bold">Pet</th>
                            <th class="small text-uppercase text-muted fw-bold">Queixa</th>
                            <th class="small text-uppercase text-muted fw-bold">Situação</th>
                            <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($atendimentos === []): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum atendimento registrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($atendimentos as $atendimento): ?>
                                <tr>
                                    <td><?= e(date_br($atendimento['data_atendimento'] ?? null, true)) ?></td>
                                    <td>
                                        <a href="<?= e(url('animais.show', ['id' => $atendimento['animal_id'] ?? 0])) ?>" class="text-decoration-none"><?= e($atendimento['pet'] ?? '') ?></a>
                                    </td>
                                    <td><small><?= e(mb_strimwidth((string) ($atendimento['queixa_principal'] ?? ''), 0, 60, '...')) ?></small></td>
                                    <td><?= status_badge($atendimento['status'] ?? null) ?></td>
                                    <td class="text-end">
                                        <a href="<?= e(url('atendimentos.show', ['id' => $atendimento['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ver atendimento"><i class="fa-solid fa-eye"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-petcare h-100">
            <div class="card-header bg-white border-0 py-3">
                <h2 class="h6 mb-0 fw-bold text-petcare"><i class="fa-solid fa-receipt me-2"></i>Contas</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Descrição</th>
                            <th class="small text-uppercase text-muted fw-bold">Vencimento</th>
                            <th class="small text-uppercase text-muted fw-bold">Valor</th>
                            <th class="small text-uppercase text-muted fw-bold">Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($contas === []): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Nenhuma conta registrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($contas as $conta): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold small"><?= e($conta['descricao'] ?? '') ?></div>
                                        <small class="text-muted"><?= e($conta['codigo'] ?? '') ?></small>
                                    </td>
                                    <td><?= e(date_br($conta['vencimento'] ?? null)) ?></td>
                                    <td><?= e(money($conta['valor'] ?? 0)) ?></td>
                                    <td><?= status_badge($conta['status'] ?? null) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 small d-flex justify-content-between">
                <span class="text-muted">Em aberto: <?= count($contasAbertas) ?></span>
                <span class="fw-semibold text-petcare"><?= e(money($totalAberto)) ?></span>
            </div>
        </div>
    </div>
</div>
