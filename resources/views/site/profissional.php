<section class="site-hero">
    <div class="site-hero-bg" aria-hidden="true">
        <span class="hero-orb hero-orb-a"></span>
        <span class="hero-orb hero-orb-b"></span>
        <span class="hero-grid"></span>
    </div>
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="hero-badge mb-4"><i class="fa-solid fa-user-doctor"></i> Área profissional</span>
                <h1 class="mb-4">Mais tempo para <span class="hero-highlight">cuidar</span>. Menos tempo para organizar.</h1>
                <p class="lead mb-4">Ferramentas pensadas para médicos-veterinários e equipes clínicas: prontuário auditável, prescrições, vacinação, agenda sem conflito e indicadores do dia.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-light btn-lg px-4 fw-semibold shadow-sm" href="<?= e(url('auth.login')) ?>"><i class="fa-solid fa-right-to-bracket me-2"></i>Acessar área profissional</a>
                    <a class="btn btn-outline-light btn-lg px-4" href="<?= e(url('site.home')) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Voltar ao início</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-panel p-4 p-lg-5">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-white border-opacity-25">
                        <span class="hero-metric-icon"><i class="fa-solid fa-stethoscope"></i></span>
                        <div>
                            <span class="small text-white-50 text-uppercase fw-semibold" style="letter-spacing:.08em;">Fluxo de atendimento</span>
                            <h2 class="h4 mb-0 mt-1">Do agendamento ao prontuário</h2>
                        </div>
                    </div>
                    <div class="timeline bg-white text-dark p-4 rounded-4">
                        <div class="timeline-item">
                            <h3 class="h6 fw-bold mb-1">Confirme a agenda</h3>
                            <p class="small text-secondary mb-0">Horários organizados para evitar sobreposição do mesmo profissional.</p>
                        </div>
                        <div class="timeline-item">
                            <h3 class="h6 fw-bold mb-1">Registre o atendimento</h3>
                            <p class="small text-secondary mb-0">Queixa, exame físico, diagnóstico e conduta em uma única ficha.</p>
                        </div>
                        <div class="timeline-item">
                            <h3 class="h6 fw-bold mb-1">Emita receita e orientações</h3>
                            <p class="small text-secondary mb-0">Prescrição pronta para impressão com dados da clínica e do tutor.</p>
                        </div>
                        <div class="timeline-item">
                            <h3 class="h6 fw-bold mb-1">Mantenha o histórico</h3>
                            <p class="small text-secondary mb-0">Prontuário com auditoria e carteirinha de vacinação atualizadas.</p>
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
            <span class="site-eyebrow">Recursos clínicos</span>
            <h2 class="display-6 fw-bold mt-2 mb-3">Tudo o que a equipe precisa em um só lugar</h2>
            <p class="text-secondary mb-0">Cada módulo foi pensado para reduzir retrabalho, padronizar processos e manter a informação clínica confiável.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($benefits as $benefit): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="card feature-card p-4 h-100">
                        <span class="feature-icon mb-4"><i class="fa-solid fa-<?= e($benefit['icon']) ?>"></i></span>
                        <h3 class="h5 fw-bold mb-2"><?= e($benefit['title']) ?></h3>
                        <p class="text-secondary mb-0"><?= e($benefit['text']) ?></p>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="site-section site-section-soft">
    <div class="container">
        <div class="text-center max-w-720 mx-auto mb-5">
            <span class="site-eyebrow">Perfis e permissões</span>
            <h2 class="display-6 fw-bold mt-2 mb-3">Cada profissional vê o que precisa</h2>
            <p class="text-secondary mb-0">Permissões por módulo e ação mantêm a rotina organizada, segura e adequada ao papel de cada colaborador.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($profiles as $profile): ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card profile-card p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="feature-icon feature-icon-sm"><i class="fa-solid fa-user-shield"></i></span>
                            <h3 class="h6 fw-bold mb-0"><?= e($profile['name']) ?></h3>
                        </div>
                        <p class="small text-secondary mb-0"><?= e($profile['description']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="site-section">
    <div class="container">
        <div class="site-cta p-4 p-md-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="hero-badge mb-3"><i class="fa-solid fa-briefcase-medical"></i> Para sua equipe</span>
                    <h2 class="display-6 fw-bold mb-3">Profissionalize a operação da sua clínica.</h2>
                    <p class="lead mb-0 opacity-75">Acesse o PetCare, configure perfis e ofereça à equipe um ambiente de trabalho mais organizado e eficiente.</p>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex flex-column gap-2">
                        <a class="btn btn-light btn-lg fw-semibold" href="<?= e(url('auth.login')) ?>"><i class="fa-solid fa-right-to-bracket me-2"></i>Entrar no PetCare</a>
                        <a class="btn btn-outline-light btn-lg" href="<?= e(url('site.home')) ?>"><i class="fa-solid fa-house me-2"></i>Página inicial</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>