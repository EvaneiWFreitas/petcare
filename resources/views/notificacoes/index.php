<?php
$title = $title ?? 'Notificações';
$rows = $rows ?? [];
$filtro = $filtro ?? 'nao_lidas';
$naoLidas = $naoLidas ?? 0;
$paginator = $paginator ?? null;
$labels = ['nao_lidas' => 'Não lidas', 'lidas' => 'Lidas', 'todas' => 'Todas'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small"><?= (int) $naoLidas ?> notificação(ões) não lida(s).</p>
    </div>
    <form method="post" action="<?= e(url('notificacoes.read')) ?>" class="m-0">
        <?= csrf_field() ?>
        <input type="hidden" name="todos" value="1">
        <button type="submit" class="btn btn-outline-petcare"><i class="fa-solid fa-check-double me-2"></i>Marcar todas como lidas</button>
    </form>
</div>

<div class="card border-0 shadow-petcare mb-4">
    <div class="card-body p-3">
        <div class="btn-group" role="group" aria-label="Filtro">
            <?php foreach ($labels as $key => $label): ?>
                <a href="<?= e(url('notificacoes.index', ['filtro' => $key])) ?>" class="btn btn-sm <?= $filtro === $key ? 'btn-petcare' : 'btn-outline-secondary' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-petcare">
    <div class="list-group list-group-flush">
        <?php if ($rows === []): ?>
            <div class="text-center py-5 text-muted"><i class="fa-regular fa-bell fa-2x d-block mb-2"></i>Nenhuma notificação encontrada.</div>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <?php $lida = (int) ($row['lida'] ?? 0) === 1; ?>
                <div class="list-group-item p-3 <?= $lida ? '' : 'bg-light' ?>">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-semibold"><?= e($row['titulo'] ?? '') ?> <?php if (!$lida): ?><span class="badge bg-warning text-dark ms-1">Nova</span><?php endif; ?></div>
                            <div class="text-muted small mb-1"><?= e($row['mensagem'] ?? '') ?></div>
                            <div class="text-muted small">
                                <?php if (!empty($row['data_evento'])): ?>Evento: <?= e(date_br((string) $row['data_evento'], true)) ?> · <?php endif; ?>
                                Criada: <?= e(date_br((string) ($row['created_at'] ?? ''), true)) ?>
                                <?php if (!empty($row['tipo'])): ?> · <span class="text-uppercase"><?= e($row['tipo']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$lida): ?>
                            <form method="post" action="<?= e(url('notificacoes.read')) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-petcare">Marcar como lida</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if (!empty($paginator)): ?>
        <div class="card-footer bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">Exibindo <?= (int) $paginator['total'] ?> registro(s) — página <?= (int) $paginator['page'] ?> de <?= (int) $paginator['pages'] ?></small>
            <?= pagination($paginator, 'notificacoes.index', ['filtro' => $filtro]) ?>
        </div>
    <?php endif; ?>
</div>
