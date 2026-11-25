<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Request;
use PetCare\Services\LicenseService;

final class LicencaController extends Controller
{
    public function index(): void
    {
        $this->guard();

        $this->render('licenca/index', [
            'title' => 'Visão geral do licenciamento',
            'status' => license_status(),
            'licencas' => LicenseService::all(),
            'settings' => LicenseService::settings(),
            'block_date' => LicenseService::blockDate(),
        ]);
    }

    public function configuracao(): void
    {
        $this->guard();

        $this->render('licenca/configuracao', [
            'title' => 'Configuração do prazo de funcionamento',
            'settings' => LicenseService::settings(),
            'block_date' => LicenseService::blockDate(),
        ]);
    }

    public function salvarConfiguracao(): void
    {
        $this->guard();

        $dias = (int) Request::post('dias_iniciais', 0);
        $avisos = trim((string) Request::post('avisos', ''));
        if ($dias < 1 || $dias > 3650) {
            flash('error', 'O prazo inicial deve estar entre 1 e 3650 dias.');
            $this->redirect('licenca.configuracao');
        }

        LicenseService::saveSettings($dias, $avisos);
        log_action('configurou_prazo', 'licenciamento', null, 'Prazo inicial: ' . $dias . ' dias. Avisos: ' . $avisos);
        flash('success', 'Configuração do prazo salva com sucesso.');
        $this->redirect('licenca.configuracao');
    }

    public function salvarDataBloqueio(): void
    {
        $this->guard();

        $data = trim((string) Request::post('data_bloqueio', ''));
        if ($data === '') {
            flash('error', 'Informe a data do bloqueio.');
            $this->redirect('licenca.configuracao');
        }

        try {
            $license = LicenseService::setBlockDate((int) LicenseService::localClinic()['id'], $data);
            $bloqueio = LicenseService::blockDate();
            flash('success', 'Data do bloqueio definida para ' . date_br($bloqueio ?? '') . '. Vencimento: ' . date_br((string) ($license['data_vencimento'] ?? '')) . '.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('licenca.configuracao');
    }

    public function prorrogacao(): void
    {
        $this->guard();

        $this->render('licenca/prorrogacao', [
            'title' => 'Prorrogação de licenças',
            'licencas' => LicenseService::all(),
        ]);
    }

    public function prorrogar(): void
    {
        $this->guard();

        $clinicId = (int) Request::post('clinica_id', 0);
        $dias = (int) Request::post('dias', 0);
        if ($clinicId <= 0 || $dias < 1 || $dias > 3650) {
            flash('error', 'Informe uma clínica e um prazo entre 1 e 3650 dias.');
            $this->redirect('licenca.prorrogacao');
        }

        try {
            $license = LicenseService::extend($clinicId, $dias);
            flash('success', 'Licença prorrogada/reativada com sucesso. Novo vencimento: ' . date_br((string) ($license['data_vencimento'] ?? null)) . '.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('licenca.prorrogacao');
    }

    public function ativar(): void
    {
        $this->guard();

        $clinicId = (int) Request::post('clinica_id', 0);
        if ($clinicId <= 0) {
            flash('error', 'Clínica inválida.');
            $this->redirect('licenca.index');
        }

        try {
            $license = LicenseService::activate($clinicId);
            flash('success', 'Licença inicial ativada. Vencimento: ' . date_br((string) ($license['data_vencimento'] ?? null)) . '.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('licenca.index');
    }

    public function ilimitadas(): void
    {
        $this->guard();

        $this->render('licenca/ilimitadas', [
            'title' => 'Licenças ilimitadas',
            'licencas' => LicenseService::all(),
        ]);
    }

    public function ativarPermanente(): void
    {
        $this->guard();

        $clinicId = (int) Request::post('clinica_id', 0);
        if ($clinicId <= 0) {
            flash('error', 'Clínica inválida.');
            $this->redirect('licenca.ilimitadas');
        }

        try {
            LicenseService::setPermanent($clinicId, true);
            log_action('ativou_licenca_ilimitada', 'licenciamento', $clinicId, 'Licença ilimitada ativada.');
            flash('success', 'Licença ilimitada ativada. O sistema não será bloqueado por vencimento.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('licenca.ilimitadas');
    }

    public function desativarPermanente(): void
    {
        $this->guard();

        $clinicId = (int) Request::post('clinica_id', 0);
        $dias = (int) Request::post('dias', 0);
        if ($clinicId <= 0 || $dias < 1 || $dias > 3650) {
            flash('error', 'Informe um prazo entre 1 e 3650 dias para voltar a licença com vencimento.');
            $this->redirect('licenca.ilimitadas');
        }

        try {
            $license = LicenseService::setPermanent($clinicId, false, $dias);
            log_action('desativou_licenca_ilimitada', 'licenciamento', $clinicId, 'Licença ilimitada desativada.');
            flash('success', 'Licença com vencimento reativada. Novo vencimento: ' . date_br((string) ($license['data_vencimento'] ?? null)) . '.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('licenca.ilimitadas');
    }

    public function historico(): void
    {
        $this->guard();

        $licencaId = (int) Request::get('licenca_id', 0);
        $this->render('licenca/historico', [
            'title' => 'Histórico de alterações',
            'historico' => LicenseService::history($licencaId > 0 ? $licencaId : null),
            'licencas' => LicenseService::all(),
            'licenca_id' => $licencaId,
        ]);
    }

    public function situacao(): void
    {
        $this->guard();

        $this->render('licenca/situacao', [
            'title' => 'Situação dos sistemas licenciados',
            'status' => license_status(),
            'licencas' => LicenseService::all(),
        ]);
    }

    public function expirada(): void
    {
        $this->render('licenca/expirada', [
            'title' => 'LICENÇA DO PETCARE EXPIRADA',
            'status' => license_status(),
        ], 'auth');
    }

    private function guard(): void
    {
        if (!is_superadmin()) {
            abort(403, 'Área restrita ao proprietário do PetCare.');
        }
    }
}
