<section class="hero-carousel-wrap">
    <?php
    $slides = $slides ?? [];
    if ($slides === []) {
        $slides = [[
            'titulo' => 'Cuidado inteligente para a <span class="text-gradient-light">sua cl?nica</span>.',
            'subtitulo' => 'Sistema de gest?o veterin?ria',
            'descricao' => 'O PetCare re?ne agenda, prontu?rios, vacina??o, estoque e financeiro em uma plataforma moderna, pensada para dar mais efici?ncia ? equipe e mais seguran?a aos tutores.',
            'badge' => 'PetCare',
            'imagem' => '',
            'botao_texto' => 'Acessar sistema',
            'botao_link' => url('auth.login'),
            'botao2_texto' => 'Conhecer servi?os',
            'botao2_link' => '#servicos',
        ]];
    }
    ?>
    <div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="<?= count($slides) > 1 ? 'carousel' : 'false' ?>" data-bs-interval="6000">
        <?php if (count($slides) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($slides as $i => $_): ?>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= e((string) $i) ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= e((string) ($i + 1)) ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="carousel-inner">
            <?php foreach ($slides as $i => $slide): ?>
                <?php
                $img = trim((string) ($slide['imagem'] ?? ''));
                $bgStyle = $img !== '' ? "background-image:url('" . e(upload_url($img)) . "');" : '';
                $link = trim((string) ($slide['botao_link'] ?? ''));
                if ($link !== '' && !preg_match('#^https?://#i', $link) && !str_starts_with($link, '#') && !str_starts_with($link, '/')) { $link = '/' . ltrim($link, '/'); }
                $titulo = (string) ($slide['titulo'] ?? '');
                $hasHtml = str_contains($titulo, '<span');
                ?>
                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                    <div class="hero-slide" style="<?= $bgStyle ?>">
                        <div class="hero-slide-overlay"></div>
                        <div class="hero-slide-pattern" aria-hidden="true"></div>
                        <div class="container position-relative">
                            <div class="row min-vh-hero align-items-center py-5">
                                <div class="col-lg-7 col-xl-6">
                                    <?php if (!empty($slide['badge']) || !empty($slide['subtitulo'])): ?>
                                        <div class="hero-eyebrow hero-eyebrow-light mb-4">
                                            <span class="hero-eyebrow-line"></span>
                                            <span><?= e((string) ($slide['badge'] ?? $slide['subtitulo'] ?? '')) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <h1 class="hero-title text-white mb-4"><?= $hasHtml ? $titulo : e($titulo) ?></h1>
                                    <?php if (!empty($slide['descricao'])): ?>
                                        <p class="hero-lead text-white-75 mb-4"><?= e((string) $slide['descricao']) ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex flex-wrap gap-3">
                                        <?php if (!empty($slide['botao_texto']) && $link !== ''): ?>
                                            <a class="btn btn-petcare btn-lg px-4" href="<?= e($link) ?>"><?= e((string) $slide['botao_texto']) ?> <i class="fa-solid fa-arrow-right ms-2"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($slide['botao2_texto']) && !empty($slide['botao2_link'])): ?>
                                            <a class="btn btn-outline-light btn-lg px-4" href="<?= e((string) $slide['botao2_link']) ?>"><?= e((string) $slide['botao2_texto']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($slides) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Pr?ximo</span></button>
        <?php endif; ?>
    </div>
</section>

<section class="trust-strip">
    <div class="container">
        <div class="trust-strip-inner">
            <div class="trust-item">
                <span class="trust-icon"><i class="fa-solid fa-calendar-check"></i></span>
                <div><strong>Agenda inteligente</strong><span>Sem sobreposi??o</span></div>
            </div>
            <div class="trust-item">
                <span class="trust-icon"><i class="fa-solid fa-file-medical"></i></span>
                <div><strong>Prontu?rio completo</strong><span>Hist?rico cl?nico</span></div>
            </div>
            <div class="trust-item">
                <span class="trust-icon"><i class="fa-solid fa-lock"></i></span>
                <div><strong>Acesso seguro</strong><span>Perfis e permiss?es</span></div>
            </div>
            <div class="trust-item">
                <span class="trust-icon"><i class="fa-solid fa-chart-pie"></i></span>
                <div><strong>Gest?o completa</strong><span>Cl?nica e financeiro</span></div>
            </div>
        </div>
    </div>
</section>

<section class="site-section bg-white" id="servicos">
    <div class="container">
        <div class="text-center max-w-720 mx-auto mb-5">
            <span class="site-eyebrow">Nossos servi?os</span>
            <h2 class="section-title mt-2 mb-3">Servi?os profissionais para o cuidado do seu pet.</h2>
            <p class="text-secondary mb-0">Conhe?a os servi?os dispon?veis na cl?nica, com transpar?ncia de dura??o e informa??es gerenciadas pelo portal administrativo.</p>
        </div>
        <div class="row g-4">
            <?php if (($servicos ?? []) === []): ?>
                <div class="col-12">
                    <div class="site-empty-card text-center">
                        <i class="fa-solid fa-layer-group"></i>
                        <p class="mb-0 text-secondary">Novos servi?os em breve.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($servicos as $servico): ?>
                    <?php
                    $nome = (string) ($servico['nome'] ?? '');
                    $icon = 'paw';
                    $n = mb_strtolower($nome);
                    if (str_contains($n, 'banho')) { $icon = 'shower'; }
                    elseif (str_contains($n, 'tosa')) { $icon = 'scissors'; }
                    elseif (str_contains($n, 'unha')) { $icon = 'hand-sparkles'; }
                    elseif (str_contains($n, 'hidrata')) { $icon = 'droplet'; }
                    elseif (str_contains($n, 'higien')) { $icon = 'soap'; }
                    elseif (str_contains($n, 'consulta')) { $icon = 'stethoscope'; }
                    $duracao = (int) ($servico['duracao_minutos'] ?? 0);
                    $preco = (float) ($servico['preco'] ?? 0);
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="service-card h-100">
                            <div class="service-card-top">
                                <span class="service-icon"><i class="fa-solid fa-<?= e($icon) ?>"></i></span>
                                <?php if ($duracao > 0): ?>
                                    <span class="service-num"><i class="fa-regular fa-clock me-1"></i><?= e((string) $duracao) ?> min</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="h5 fw-bold mb-2"><?= e($nome) ?></h3>
                            <p class="text-secondary mb-4 service-desc"><?= e((string) ($servico['descricao'] ?? 'Atendimento realizado com cuidado e aten??o ao bem-estar do pet.')) ?></p>
                            <div class="service-card-footer">
                                <span class="small text-secondary">Valor</span>
                                <span class="service-price"><?= $preco > 0 ? e(money($preco)) : '<span class="text-secondary fw-semibold">Consultar</span>' ?></span>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="site-section section-soft" id="diferenciais">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="site-eyebrow">Por que escolher o PetCare</span>
                <h2 class="section-title mt-2 mb-4">Uma experi?ncia mais profissional para sua cl?nica.</h2>
                <p class="text-secondary mb-4">Centralize a opera??o, reduza retrabalho e tenha uma vis?o clara do que acontece na cl?nica ? do atendimento ao financeiro.</p>
                <div class="d-flex flex-column gap-3">
                    <div class="benefit-item">
                        <span class="benefit-icon"><i class="fa-solid fa-bolt"></i></span>
                        <div>
                            <h3 class="h6 fw-bold mb-1">Rotina mais ?gil</h3>
                            <p class="text-secondary small mb-0">Agendamentos, atendimentos e registros em um fluxo simples e r?pido.</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon"><i class="fa-solid fa-shield-halved"></i></span>
                        <div>
                            <h3 class="h6 fw-bold mb-1">Informa??es seguras</h3>
                            <p class="text-secondary small mb-0">Perfis de acesso e dados organizados para proteger a opera??o da cl?nica.</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon"><i class="fa-solid fa-chart-simple"></i></span>
                        <div>
                            <h3 class="h6 fw-bold mb-1">Vis?o gerencial</h3>
                            <p class="text-secondary small mb-0">Acompanhe indicadores e resultados para tomar decis?es com confian?a.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="visual-panel">
                    <div class="visual-panel-header">
                        <span class="site-eyebrow mb-0">Vis?o do sistema</span>
                        <span class="mockup-badge-live"><span class="mockup-live-dot"></span>Ativo</span>
                    </div>
                    <div class="visual-kpis">
                        <div class="visual-kpi">
                            <span class="visual-kpi-label">Pacientes ativos</span>
                            <strong>248</strong>
                            <span class="visual-kpi-trend"><i class="fa-solid fa-arrow-up"></i> +12%</span>
                        </div>
                        <div class="visual-kpi">
                            <span class="visual-kpi-label">Retorno em dia</span>
                            <strong>96%</strong>
                            <span class="visual-kpi-trend"><i class="fa-solid fa-arrow-up"></i> +4%</span>
                        </div>
                    </div>
                    <div class="visual-list">
                        <div class="visual-list-item">
                            <span class="visual-list-icon bg-teal-soft"><i class="fa-solid fa-syringe"></i></span>
                            <div class="flex-grow-1"><div class="fw-semibold small">Vacina V10</div><div class="text-secondary" style="font-size:.75rem">Thor ? Hoje, 14:00</div></div>
                            <span class="badge-status badge-status-ok">Confirmado</span>
                        </div>
                        <div class="visual-list-item">
                            <span class="visual-list-icon bg-green-soft"><i class="fa-solid fa-stethoscope"></i></span>
                            <div class="flex-grow-1"><div class="fw-semibold small">Consulta de rotina</div><div class="text-secondary" style="font-size:.75rem">Mia ? Hoje, 15:30</div></div>
                            <span class="badge-status badge-status-wait">Aguardando</span>
                        </div>
                        <div class="visual-list-item">
                            <span class="visual-list-icon bg-blue-soft"><i class="fa-solid fa-file-medical"></i></span>
                            <div class="flex-grow-1"><div class="fw-semibold small">Exame laboratorial</div><div class="text-secondary" style="font-size:.75rem">Bob ? Amanh?, 09:00</div></div>
                            <span class="badge-status badge-status-info">Agendado</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="site-section section-dark">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <span class="site-eyebrow site-eyebrow-light">Fluxo de trabalho</span>
                <h2 class="section-title text-white mt-2 mb-3">Simples de come?ar. Poderoso no dia a dia.</h2>
                <p class="text-white-70 mb-4">O PetCare foi desenhado para se adaptar ? rotina da cl?nica, com uma experi?ncia clara para toda a equipe.</p>
                <a class="btn btn-light btn-lg px-4 fw-semibold" href="<?= e(url('site.profissional')) ?>">
                    ?rea profissional <i class="fa-solid fa-arrow-right ms-2"></i>
                </a>
            </div>
            <div class="col-lg-7">
                <div class="steps-list">
                    <?php foreach ($steps as $step): ?>
                        <div class="step-item">
                            <div class="step-marker"><?= e($step['number']) ?></div>
                            <div class="step-content">
                                <h3 class="h5 fw-bold text-white mb-1"><?= e($step['title']) ?></h3>
                                <p class="text-white-70 mb-0"><?= e($step['text']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="site-section bg-white">
    <div class="container">
        <div class="cta-banner">
            <div class="cta-banner-bg" aria-hidden="true"></div>
            <div class="row align-items-center g-4 position-relative">
                <div class="col-lg-7">
                    <span class="site-eyebrow">Pronto para come?ar?</span>
                    <h2 class="section-title mt-2 mb-3">Transforme a gest?o da sua cl?nica veterin?ria.</h2>
                    <p class="text-secondary mb-0 fs-5">Tenha mais controle, organiza??o e uma experi?ncia profissional para sua equipe e para os tutores.</p>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex flex-column flex-sm-row flex-lg-column gap-3 justify-content-lg-end">
                        <a class="btn btn-petcare btn-lg px-4" href="<?= e(url('auth.login')) ?>"><i class="fa-solid fa-right-to-bracket me-2"></i>Entrar no sistema</a>
                        <a class="btn btn-hero-outline btn-lg px-4" href="<?= e(url('portal.login')) ?>"><i class="fa-solid fa-paw me-2"></i>Portal do tutor</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
