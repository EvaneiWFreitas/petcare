<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;

final class SiteController extends Controller
{
        public function home(): void
    {
        $servicos = Database::fetchAll(
            'SELECT id, nome, descricao, preco, duracao_minutos FROM servicos ORDER BY nome ASC, id ASC'
        );
        $slides = [];
        try {
            $slides = Database::fetchAll(
                'SELECT id, titulo, subtitulo, descricao, badge, imagem, botao_texto, botao_link, ordem FROM site_carrossel WHERE ativo = 1 ORDER BY ordem ASC, id ASC'
            );
        } catch (\Throwable $e) {
            $slides = [];
        }

        $this->render('site/home', [
            'title' => 'PetCare | Gest?o veterin?ria',
            'servicos' => $servicos,
            'slides' => $slides,
            'steps' => [
                ['number' => '01', 'title' => 'Cadastre a equipe', 'text' => 'Configure perfis, permiss?es e os profissionais da cl?nica.'],
                ['number' => '02', 'title' => 'Organize a rotina', 'text' => 'Tutores, animais, agenda e atendimentos conectados desde o primeiro dia.'],
                ['number' => '03', 'title' => 'Acompanhe os resultados', 'text' => 'Painel com indicadores cl?nicos, operacionais e financeiros.'],
            ],
        ], 'site');
    }

    public function profissional(): void
    {
        $this->render('site/profissional', [
            'title' => 'Para profissionais | PetCare',
            'benefits' => [
                ['icon' => 'stethoscope', 'title' => 'Atendimento ágil', 'text' => 'Registre queixa, exame físico, diagnóstico, procedimentos e medicamentos em uma única tela.'],
                ['icon' => 'file-medical', 'title' => 'Prontuário auditável', 'text' => 'Cada alteração é registrada, garantindo rastreabilidade e segurança clínica.'],
                ['icon' => 'prescription-bottle', 'title' => 'Receitas e exames', 'text' => 'Emita prescrições, solicite exames e acompanhe resultados sem sair do sistema.'],
                ['icon' => 'syringe', 'title' => 'Protocolo de vacinas', 'text' => 'Histórico por animal, lote aplicado e lembretes de próxima dose.'],
                ['icon' => 'calendar-check', 'title' => 'Agenda sem conflito', 'text' => 'A sistemática bloqueia sobreposição de horários para o mesmo veterinário.'],
                ['icon' => 'gauge-high', 'title' => 'Indicadores claros', 'text' => 'Visualize atendimentos, receitas e pendências do dia logo ao entrar.'],
            ],
            'profiles' => [
                ['name' => 'Administrador', 'description' => 'Acesso completo a todos os módulos e configurações.'],
                ['name' => 'Veterinário', 'description' => 'Atendimentos, prontuários, receitas, exames e cirurgias.'],
                ['name' => 'Recepcionista', 'description' => 'Tutores, animais e organização da agenda.'],
                ['name' => 'Financeiro', 'description' => 'Contas, pagamentos, fluxo e relatórios.'],
                ['name' => 'Estoquista', 'description' => 'Farmácia, movimentações e compras.'],
                ['name' => 'Auxiliar', 'description' => 'Apoio em vacinação e exames.'],
                ['name' => 'Banho e tosa', 'description' => 'Agenda e atendimentos de estética e higiene.'],
                ['name' => 'Tutor', 'description' => 'Portal separado com histórico e agendamentos do pet.'],
            ],
        ], 'site');
    }
}
