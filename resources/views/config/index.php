<?php
$title = $title ?? 'Configurações';
$settings = $settings ?? [];
$v = static fn(string $key, string $default = ''): string => (string) ($settings[$key] ?? $default);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-petcare"><?= e($title) ?></h1>
        <p class="text-muted mb-0 small">Defina identidade, sistema e integrações da clínica.</p>
    </div>
    <?php if (can('configuracoes', 'approve')): ?>
        <a href="<?= e(url('config.backup')) ?>" class="btn btn-outline-petcare"><i class="fa-solid fa-database me-2"></i>Gerar backup</a>
    <?php endif; ?>
</div>

<form method="post" action="<?= e(url('config.update')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="card border-0 shadow-petcare mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Identidade</h2></div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-semibold" for="clinica_nome">Nome da clínica</label><input class="form-control" id="clinica_nome" name="clinica_nome" value="<?= e($v('clinica_nome')) ?>"></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="slogan">Slogan</label><input class="form-control" id="slogan" name="slogan" value="<?= e($v('slogan')) ?>"></div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="logo">Logo</label>
                    <?php if ($v('logo') !== ''): ?>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="<?= e(upload_url($v('logo'))) ?>" alt="Logo atual" class="brand-logo-img">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="remover_logo" name="remover_logo">
                                <label class="form-check-label small" for="remover_logo">Remover logo atual</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input class="form-control" type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">JPG, PNG, WEBP ou GIF. A imagem será usada na identidade da clínica.</div>
                </div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="cnpj">CNPJ</label><input class="form-control" id="cnpj" name="cnpj" value="<?= e($v('cnpj')) ?>"></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="endereco">Endereço</label><input class="form-control" id="endereco" name="endereco" value="<?= e($v('endereco')) ?>"></div>
                <div class="col-md-3"><label class="form-label fw-semibold" for="telefone">Telefone</label><input class="form-control" id="telefone" name="telefone" value="<?= e($v('telefone')) ?>"></div>
                <div class="col-md-3"><label class="form-label fw-semibold" for="whatsapp">WhatsApp</label><input class="form-control" id="whatsapp" name="whatsapp" value="<?= e($v('whatsapp')) ?>"></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="email">E-mail</label><input class="form-control" type="email" id="email" name="email" value="<?= e($v('email')) ?>"></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="horario_funcionamento">Horário de funcionamento</label><input class="form-control" id="horario_funcionamento" name="horario_funcionamento" value="<?= e($v('horario_funcionamento')) ?>"></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-petcare mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Fiscal e documentos</h2></div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label fw-semibold" for="dados_emitente">Dados do emitente</label><textarea class="form-control" id="dados_emitente" name="dados_emitente" rows="3"><?= e($v('dados_emitente')) ?></textarea></div>
                <div class="col-md-4"><label class="form-label fw-semibold" for="crmv_padrao">CRMV padrão</label><input class="form-control" id="crmv_padrao" name="crmv_padrao" value="<?= e($v('crmv_padrao')) ?>"></div>
                <div class="col-md-4"><label class="form-label fw-semibold" for="resolucao_sanitaria">Resolução sanitária</label><input class="form-control" id="resolucao_sanitaria" name="resolucao_sanitaria" value="<?= e($v('resolucao_sanitaria')) ?>"></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-petcare mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Sistema</h2></div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label fw-semibold" for="moeda">Moeda</label><input class="form-control" id="moeda" name="moeda" value="<?= e($v('moeda', 'BRL')) ?>"></div>
                <div class="col-md-3"><label class="form-label fw-semibold" for="fuso_horario">Fuso horário</label><input class="form-control" id="fuso_horario" name="fuso_horario" value="<?= e($v('fuso_horario', 'America/Sao_Paulo')) ?>"></div>
                <div class="col-md-3"><label class="form-label fw-semibold" for="retencao_dados_anos">Retenção de dados (anos)</label><input class="form-control" type="number" min="0" id="retencao_dados_anos" name="retencao_dados_anos" value="<?= e($v('retencao_dados_anos', '5')) ?>"></div>
                <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" id="backup_automatico" name="backup_automatico" value="1" <?= checked($v('backup_automatico', '0'), '1') ?>><label class="form-check-label" for="backup_automatico">Backup automático</label></div></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-petcare mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Integrações</h2></div>
        <div class="card-body p-4">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="email_integracao" name="email_integracao" value="1" <?= checked($v('email_integracao', '0'), '1') ?>>
                <label class="form-check-label" for="email_integracao">Integração de e-mail</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="whatsapp_integracao" name="whatsapp_integracao" value="1" <?= checked($v('whatsapp_integracao', '0'), '1') ?>>
                <label class="form-check-label" for="whatsapp_integracao">Integração de WhatsApp</label>
            </div>
            <div class="form-text">Não há envio simulado; integrações desativadas até configuração externa.</div>
        </div>
    </div>

    <div class="card border-0 shadow-petcare mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Impressão</h2></div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-semibold" for="cabecalho_impressao">Cabeçalho de impressão</label><textarea class="form-control" id="cabecalho_impressao" name="cabecalho_impressao" rows="4"><?= e($v('cabecalho_impressao')) ?></textarea></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="rodape_impressao">Rodapé de impressão</label><textarea class="form-control" id="rodape_impressao" name="rodape_impressao" rows="4"><?= e($v('rodape_impressao')) ?></textarea></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-4">
        <button type="submit" class="btn btn-petcare"><i class="fa-solid fa-save me-2"></i>Salvar configurações</button>
    </div>
</form>
