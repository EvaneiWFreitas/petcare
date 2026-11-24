<?php

$title = $title ?? 'Ficha do animal';
$animal = $animal ?? [];
$tutor = $tutor ?? [];
$sections = $sections ?? [];
$prontuarios = $prontuarios ?? [];
$vacinacoes = $vacinacoes ?? [];
$exames = $exames ?? [];
$receitas = $receitas ?? [];
$banhos = $banhos ?? [];
$links = $links ?? [];
$recordId = $recordId ?? ($animal['id'] ?? 0);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <?php if (!empty($animal['foto'])): ?>
            <img src="<?= e(upload_url($animal['foto'])) ?>" alt="<?= e($animal['nome'] ?? 'Animal') ?>" class="rounded-circle object-fit-cover border shadow-sm" width="88" height="88">
        <?php else: ?>
            <div class="rounded-circle bg-petcare-soft d-flex align-items-center justify-content-center text-petcare" style="width:88px;height:88px;">
                <i class="fa-solid fa-paw fa-2x"></i>
            </div>
        <?php endif; ?>
        <div>
            <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($animal['nome'] ?? 'Animal') ?></h1>
            <p class="text-muted mb-1 small">
                <?= e($animal['codigo'] ?? '') ?>
                · <?= e($animal['especie'] ?? '—') ?>
                <?php if (!empty($animal['raca'])): ?> / <?= e($animal['raca']) ?><?php endif; ?>
                · <?= e(age($animal['data_nascimento'] ?? null)) ?>
            </p>
            <a href="<?= e($links['tutor'] ?? url('clientes.show', ['id' => $tutor['id'] ?? 0])) ?>" class="text-decoration-none small">
                <i class="fa-solid fa-user me-1"></i>Tutor: <?= e($tutor['nome'] ?? '—') ?>
            </a>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e($links['editar'] ?? url('animais.edit', ['id' => $recordId])) ?>" class="btn btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Editar</a>
        <a href="<?= e($links['voltar'] ?? url('animais.index')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<ul class="nav nav-tabs" id="fichaAnimalTabs" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" id="tab-visao" data-bs-toggle="tab" data-bs-target="#pane-visao" type="button" role="tab" aria-controls="pane-visao" aria-selected="true">Visão geral</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="tab-prontuarios" data-bs-toggle="tab" data-bs-target="#pane-prontuarios" type="button" role="tab" aria-controls="pane-prontuarios" aria-selected="false">Prontuários</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="tab-vacinas" data-bs-toggle="tab" data-bs-target="#pane-vacinas" type="button" role="tab" aria-controls="pane-vacinas" aria-selected="false">Vacinas</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="tab-exames" data-bs-toggle="tab" data-bs-target="#pane-exames" type="button" role="tab" aria-controls="pane-exames" aria-selected="false">Exames</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="tab-receitas" data-bs-toggle="tab" data-bs-target="#pane-receitas" type="button" role="tab" aria-controls="pane-receitas" aria-selected="false">Receitas</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="tab-estetica" data-bs-toggle="tab" data-bs-target="#pane-estetica" type="button" role="tab" aria-controls="pane-estetica" aria-selected="false">Estética</button></li>
</ul>

<div class="tab-content pt-4" id="fichaAnimalContent">
    <div class="tab-pane fade show active" id="pane-visao" role="tabpanel" aria-labelledby="tab-visao">
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
    </div>

    <div class="tab-pane fade" id="pane-prontuarios" role="tabpanel" aria-labelledby="tab-prontuarios">
        <div class="card border-0 shadow-petcare">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                <h2 class="h6 mb-0 fw-bold text-petcare">Prontuários</h2>
                <a href="<?= e($links['prontuario'] ?? '') ?>" class="btn btn-sm btn-petcare"><i class="fa-solid fa-plus me-1"></i>Novo prontuário</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Data</th>
                            <th class="small text-uppercase text-muted fw-bold">Veterinário</th>
                            <th class="small text-uppercase text-muted fw-bold">Diagnóstico</th>
                            <th class="small text-uppercase text-muted fw-bold">Procedimentos</th>
                            <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($prontuarios === []): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum prontuário registrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($prontuarios as $item): ?>
                                <tr>
                                    <td><?= e(date_br($item['data_atendimento'] ?? null, true)) ?></td>
                                    <td><?= e($item['veterinario'] ?? '') ?></td>
                                    <td><small><?= e(mb_strimwidth((string) ($item['diagnostico'] ?? ''), 0, 70, '...')) ?></small></td>
                                    <td><small><?= e(mb_strimwidth((string) ($item['procedimentos'] ?? ''), 0, 70, '...')) ?></small></td>
                                    <td class="text-end"><a href="<?= e(url('prontuarios.show', ['id' => $item['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ver prontuário"><i class="fa-solid fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-vacinas" role="tabpanel" aria-labelledby="tab-vacinas">
        <div class="card border-0 shadow-petcare">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                <h2 class="h6 mb-0 fw-bold text-petcare">Vacinações</h2>
                <a href="<?= e($links['vacina'] ?? '') ?>" class="btn btn-sm btn-petcare"><i class="fa-solid fa-plus me-1"></i>Nova vacina</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Vacina</th>
                            <th class="small text-uppercase text-muted fw-bold">Aplicação</th>
                            <th class="small text-uppercase text-muted fw-bold">Próxima dose</th>
                            <th class="small text-uppercase text-muted fw-bold">Lote</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vacinacoes === []): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Nenhuma vacinação registrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($vacinacoes as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($item['vacina'] ?? '') ?></td>
                                    <td><?= e(date_br($item['data_aplicacao'] ?? null)) ?></td>
                                    <td><?= e(date_br($item['proxima_dose'] ?? null)) ?></td>
                                    <td><?= e($item['lote'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-exames" role="tabpanel" aria-labelledby="tab-exames">
        <div class="card border-0 shadow-petcare">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                <h2 class="h6 mb-0 fw-bold text-petcare">Exames</h2>
                <a href="<?= e($links['exame'] ?? '') ?>" class="btn btn-sm btn-petcare"><i class="fa-solid fa-plus me-1"></i>Novo exame</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Código</th>
                            <th class="small text-uppercase text-muted fw-bold">Tipo</th>
                            <th class="small text-uppercase text-muted fw-bold">Solicitação</th>
                            <th class="small text-uppercase text-muted fw-bold">Resultado</th>
                            <th class="small text-uppercase text-muted fw-bold">Situação</th>
                            <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($exames === []): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum exame registrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($exames as $item): ?>
                                <tr>
                                    <td><?= e($item['codigo'] ?? '') ?></td>
                                    <td><?= e($item['tipo'] ?? '') ?></td>
                                    <td><?= e(date_br($item['data_solicitacao'] ?? null)) ?></td>
                                    <td><?= e(date_br($item['data_resultado'] ?? null)) ?></td>
                                    <td><?= status_badge($item['status'] ?? null) ?></td>
                                    <td class="text-end"><a href="<?= e(url('exames.show', ['id' => $item['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ver exame"><i class="fa-solid fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-receitas" role="tabpanel" aria-labelledby="tab-receitas">
        <div class="card border-0 shadow-petcare">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                <h2 class="h6 mb-0 fw-bold text-petcare">Receitas</h2>
                <a href="<?= e($links['receita'] ?? '') ?>" class="btn btn-sm btn-petcare"><i class="fa-solid fa-plus me-1"></i>Nova receita</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Código</th>
                            <th class="small text-uppercase text-muted fw-bold">Data</th>
                            <th class="small text-uppercase text-muted fw-bold">Veterinário</th>
                            <th class="small text-uppercase text-muted fw-bold">Orientações</th>
                            <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($receitas === []): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhuma receita registrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($receitas as $item): ?>
                                <tr>
                                    <td><?= e($item['codigo'] ?? '') ?></td>
                                    <td><?= e(date_br($item['data_prescricao'] ?? null)) ?></td>
                                    <td><?= e($item['veterinario'] ?? '') ?></td>
                                    <td><small><?= e(mb_strimwidth((string) ($item['orientacoes'] ?? ''), 0, 70, '...')) ?></small></td>
                                    <td class="text-end"><a href="<?= e(url('receitas.show', ['id' => $item['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ver receita"><i class="fa-solid fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-estetica" role="tabpanel" aria-labelledby="tab-estetica">
        <div class="card border-0 shadow-petcare">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                <h2 class="h6 mb-0 fw-bold text-petcare">Banho e tosa</h2>
                <a href="<?= e($links['estetica'] ?? '') ?>" class="btn btn-sm btn-petcare"><i class="fa-solid fa-plus me-1"></i>Novo serviço</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-uppercase text-muted fw-bold">Código</th>
                            <th class="small text-uppercase text-muted fw-bold">Data</th>
                            <th class="small text-uppercase text-muted fw-bold">Serviço</th>
                            <th class="small text-uppercase text-muted fw-bold">Valor</th>
                            <th class="small text-uppercase text-muted fw-bold">Situação</th>
                            <th class="text-end small text-uppercase text-muted fw-bold">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($banhos === []): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum serviço estético registrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($banhos as $item): ?>
                                <tr>
                                    <td><?= e($item['codigo'] ?? '') ?></td>
                                    <td><?= e(date_br($item['data_servico'] ?? null, true)) ?></td>
                                    <td><?= e($item['servico'] ?? '') ?></td>
                                    <td><?= e(money($item['valor'] ?? 0)) ?></td>
                                    <td><?= status_badge($item['status'] ?? null) ?></td>
                                    <td class="text-end"><a href="<?= e(url('banho_tosa.show', ['id' => $item['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ver serviço"><i class="fa-solid fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
