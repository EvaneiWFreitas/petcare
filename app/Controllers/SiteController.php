<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;

final class SiteController extends Controller
{
    public function home(): void
    {
        $this->render('site/home', [
            'title' => 'PetCare | Gestão veterinária',
            'features' => [
                ['icon' => 'calendar-days', 'title' => 'Agenda inteligente', 'text' => 'Agendamentos com bloqueio automático de horários conflitantes por veterinário.'],
                ['icon' => 'file-medical', 'title' => 'Prontuários completos', 'text' => 'Histórico clínico, evoluções e auditoria de cada alteração em um só lugar.'],
                ['icon' => 'syringe', 'title' => 'Vacinação e carteirinha', 'text' => 'Controle de doses, lotes e próximas aplicações com carteirinha digital.'],
                ['icon' => 'boxes-stacked', 'title' => 'Farmácia e estoque', 'text' => 'Produtos, movimentações, compras e recebimento com níveis mínimos.'],
                ['icon' => 'chart-line', 'title' => 'Financeiro integrado', 'text' => 'Contas a receber, contas a pagar, pagamentos e fluxo de caixa em tempo real.'],
                ['icon' => 'chart-pie', 'title' => 'Relatórios prontos', 'text' => 'Indicadores exportáveis em tela, CSV e impressão para apoiar decisões.'],
            ],
            'steps' => [
                ['number' => '01', 'title' => 'Cadastre a equipe', 'text' => 'Configure perfis, permissões e os profissionais da clínica.'],
                ['number' => '02', 'title' => 'Organize a rotina', 'text' => 'Tutores, animais, agenda e atendimentos conectados desde o primeiro dia.'],
                ['number' => '03', 'title' => 'Acompanhe os resultados', 'text' => 'Painel com indicadores clínicos, operacionais e financeiros.'],
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
