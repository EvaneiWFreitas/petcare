<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $today = date('Y-m-d');

        $stats = [
            'clientes' => (int) Database::scalar('SELECT COUNT(*) FROM clientes WHERE ativo = 1'),
            'animais' => (int) Database::scalar('SELECT COUNT(*) FROM animais WHERE ativo = 1'),
            'agendados' => (int) Database::scalar("SELECT COUNT(*) FROM agendamentos WHERE data_agendamento >= CURDATE() AND status IN ('aguardando','confirmado','em_atendimento')"),
            'realizados' => (int) Database::scalar("SELECT COUNT(*) FROM agendamentos WHERE status = 'finalizado'"),
            'cancelados' => (int) Database::scalar("SELECT COUNT(*) FROM agendamentos WHERE status IN ('cancelado','falta')"),
            'atendimentos_hoje' => (int) Database::scalar('SELECT COUNT(*) FROM atendimentos WHERE DATE(data_atendimento) = CURDATE()'),
            'vacinas_proximas' => (int) Database::scalar('SELECT COUNT(*) FROM vacinacoes WHERE proxima_dose BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)'),
            'vacinas_atrasadas' => (int) Database::scalar('SELECT COUNT(*) FROM vacinacoes WHERE proxima_dose < CURDATE()'),
            'aniversariantes' => (int) Database::scalar('SELECT COUNT(*) FROM animais WHERE ativo = 1 AND MONTH(data_nascimento) = MONTH(CURDATE()) AND DAY(data_nascimento) = DAY(CURDATE())'),
            'estoque_baixo' => (int) Database::scalar('SELECT COUNT(*) FROM produtos WHERE quantidade <= estoque_minimo'),
            'produtos_vencendo' => (int) Database::scalar('SELECT COUNT(*) FROM produtos WHERE data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)'),
            'receitas_dia' => (float) Database::scalar("SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE DATE(data_pagamento) = CURDATE() AND conta_receber_id IS NOT NULL"),
            'receitas_mes' => (float) Database::scalar("SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE YEAR(data_pagamento) = YEAR(CURDATE()) AND MONTH(data_pagamento) = MONTH(CURDATE()) AND conta_receber_id IS NOT NULL"),
            'despesas_mes' => (float) Database::scalar("SELECT COALESCE(SUM(valor),0) FROM despesas WHERE YEAR(data_despesa) = YEAR(CURDATE()) AND MONTH(data_despesa) = MONTH(CURDATE())") + (float) Database::scalar("SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE YEAR(data_pagamento) = YEAR(CURDATE()) AND MONTH(data_pagamento) = MONTH(CURDATE()) AND conta_pagar_id IS NOT NULL"),
        ];
        $stats['saldo'] = $stats['receitas_mes'] - $stats['despesas_mes'];

        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = (int) date('m', strtotime("-{$i} months"));
            $year = (int) date('Y', strtotime("-{$i} months"));
            $labels[] = ['label' => strtoupper(mb_substr($this->monthName($month), 0, 3)) . '/' . substr((string) $year, -2), 'key' => sprintf('%04d-%02d', $year, $month)];
        }

        $consultas = [];
        $clientes = [];
        $receitas = [];
        $despesas = [];
        foreach ($labels as $item) {
            $consultas[] = (int) Database::scalar('SELECT COUNT(*) FROM agendamentos WHERE DATE_FORMAT(data_agendamento, "%Y-%m") = :key', ['key' => $item['key']]);
            $clientes[] = (int) Database::scalar('SELECT COUNT(*) FROM clientes WHERE DATE_FORMAT(created_at, "%Y-%m") = :key', ['key' => $item['key']]);
            $receitas[] = (float) Database::scalar('SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE conta_receber_id IS NOT NULL AND DATE_FORMAT(data_pagamento, "%Y-%m") = :key', ['key' => $item['key']]);
            $despesas[] = (float) Database::scalar('SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE conta_pagar_id IS NOT NULL AND DATE_FORMAT(data_pagamento, "%Y-%m") = :key', ['key' => $item['key']])
                + (float) Database::scalar('SELECT COALESCE(SUM(valor),0) FROM despesas WHERE DATE_FORMAT(data_despesa, "%Y-%m") = :key', ['key' => $item['key']]);
        }

        $charts = [
            'consultas_mes' => ['labels' => array_column($labels, 'label'), 'data' => $consultas],
            'clientes_mes' => ['labels' => array_column($labels, 'label'), 'data' => $clientes],
            'financeiro' => ['labels' => array_column($labels, 'label'), 'receitas' => $receitas, 'despesas' => $despesas],
        ];

        $activities = Database::fetchAll(
            'SELECT l.acao, l.modulo, COALESCE(u.nome, "Sistema") AS usuario, l.created_at
             FROM logs l LEFT JOIN usuarios u ON u.id = l.usuario_id
             ORDER BY l.created_at DESC LIMIT 6'
        );

        $upcoming = Database::fetchAll(
            'SELECT c.nome AS nome, a.nome AS pet, ag.tipo, ag.data_agendamento, ag.hora_inicio, ag.status
             FROM agendamentos ag
             INNER JOIN animais a ON a.id = ag.animal_id
             INNER JOIN clientes c ON c.id = ag.cliente_id
             WHERE ag.data_agendamento >= CURDATE() AND ag.status NOT IN ("cancelado","falta")
             ORDER BY ag.data_agendamento, ag.hora_inicio LIMIT 6'
        );

        $shortcuts = [];
        $shortcutDefinitions = [
            ['Novo cliente', 'clientes.create', 'user-plus', 'primary', 'clientes', 'create'],
            ['Novo pet', 'animais.create', 'paw', 'success', 'animais', 'create'],
            ['Agendar consulta', 'agenda.create', 'calendar-plus', 'info', 'agenda', 'create'],
            ['Registrar atendimento', 'atendimentos.create', 'stethoscope', 'primary', 'atendimentos', 'create'],
            ['Cadastrar vacina', 'vacinas.create', 'syringe', 'success', 'vacinas', 'create'],
            ['Emitir receita', 'receitas.create', 'prescription', 'info', 'receitas', 'create'],
            ['Registrar pagamento', 'financeiro.receber', 'hand-holding-dollar', 'warning', 'financeiro', 'view'],
        ];
        foreach ($shortcutDefinitions as [$label, $route, $icon, $color, $module, $action]) {
            if (can($module, $action)) {
                $shortcuts[] = ['label' => $label, 'route' => $route, 'icon' => $icon, 'color' => $color];
            }
        }

        $this->render('dashboard/index', [
            'title' => 'Painel administrativo',
            'stats' => $stats,
            'charts' => $charts,
            'activities' => $activities,
            'upcoming' => $upcoming,
            'shortcuts' => $shortcuts,
            'today' => $today,
        ]);
    }

    private function monthName(int $month): string
    {
        $months = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        return $months[$month] ?? '';
    }
}
