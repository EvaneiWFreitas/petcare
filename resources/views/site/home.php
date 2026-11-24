<section class="site-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="hero-badge mb-4"><i class="fa-solid fa-heart-pulse"></i> Gestão clínica completa</span>
                <h1 class="mb-4">Tecnologia que cuida de quem cuida dos seus pets.</h1>
                <p class="lead mb-4">O PetCare reúne agenda, prontuários, vacinação, farmácia e financeiro em um único sistema, feito para a rotina real de clínicas veterinárias.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-light btn-lg px-4 fw-semibold" href="<?= e(url('site.profissional')) ?>">
                        <i class="fa-solid fa-user-doctor me-2"></i>Conhecer para profissionais
                    </a>
                    <a class="btn btn-outline-light btn-lg px-4" href="<?= e(url('auth.login')) ?>">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Acessar o sistema
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-panel p-4 p-lg-5">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <span class="small opacity-75">Painel de hoje</span>
                            <h2 class="h4 mb-0 mt-1">Visão da clínica</h2>
                        </div>
                        <span class="hero-badge"><i class="fa-solid fa-circle-check"></i> Tudo em dia</span>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="hero-metric">
                                <i class="fa-solid fa-calendar-check"></i>
                                <div><strong>12</strong><span class="d-block small opacity-75">Agendamentos</span></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="hero-metric">
                                <i class="fa-solid fa-stethoscope"></i>
                                <div><strong>8</strong><span class="d-block small opacity-75">Atendimentos</span></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="hero-metric">
                                <i class="fa-solid fa-syringe"></i>
                                <div><strong>15</strong><span class="d-block small opacity-75">Vacinas</span></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="hero-metric">
                                <i class="fa-solid fa-chart-line"></i>
                                <div><strong>R$ 4.280</strong><span class="d-block small opacity-75">Receitas do mês</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 rounded-3 bg-white text-dark">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold">Próximo atendimento</span>
                            <span class="badge bg-petcare-soft">09:30</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="feature-icon flex-shrink-0"><i class="fa-solid fa-paw"></i></span>
                            <div>
                                <div class="fw-semibold">Luna e Tutor Mariana</div>
                                <div class="small text-secondary">Consulta de retorno · Dr. Carlos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="site-section bg-white">
    <div class="container">
        <div class="text-center max-w-720 mx-auto mb-5">
            <span class="site-eyebrow">Tudo integrado</span>
            <h2 class="display-6 fw-bold mt-2">Um sistema para toda a operação da clínica</h2>
            <p class="text-secondary mb-0">Da recepção ao financeiro, cada informação conectada para reduzir retrabalho e melhorar o cuidado.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($features as $feature): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card p-4">
                        <span class="feature-icon mb-3"><i class="fa-solid fa-<?= e($feature['icon']) ?>"></i></span>
                        <h3 class="h5 fw-bold"><?= e($feature['title']) ?></h3>
                        <p class="text-secondary mb-0"><?= e($feature['text']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="site-section bg-light">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <span class="site-eyebrow">Como funciona</span>
                <h2 class="display-6 fw-bold mt-2 mb-3">Pronto para usar na sua rotina</h2>
                <p class="text-secondary">Comece com o cadastro da equipe e dos tutores. Em poucos passos, sua clínica passa a ter agenda, histórico clínico e indicadores organizados.</p>
                <a class="btn btn-petcare mt-3" href="<?= e(url('site.profissional')) ?>">Ver recursos para a equipe <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <?php foreach ($steps as $step): ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-petcare p-4 d-flex gap-3 align-items-start">
                                <span class="step-number flex-shrink-0"><?= e($step['number']) ?></span>
                                <div>
                                    <h3 class="h6 fw-bold mb-1"><?= e($step['title']) ?></h3>
                                    <p class="text-secondary mb-0"><?= e($step['text']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="site-section">
    <div class="container">
        <div class="site-cta p-5 text-center">
            <h2 class="display-6 fw-bold mb-3">Cuide melhor com informação organizada</h2>
            <p class="lead mb-4 opacity-75">Acesse o painel, configure sua equipe e comece a usar o PetCare hoje mesmo.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a class="btn btn-light btn-lg px-4 fw-semibold" href="<?= e(url('auth.login')) ?>"><i class="fa-solid fa-right-to-bracket me-2"></i>Entrar no sistema</a>
                <a class="btn btn-outline-light btn-lg px-4" href="<?= e(url('portal.login')) ?>"><i class="fa-solid fa-paw me-2"></i>Acessar portal do tutor</a>
            </div>
        </div>
    </div>
</section>
