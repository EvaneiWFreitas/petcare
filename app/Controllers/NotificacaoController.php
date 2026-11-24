<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Core\Session;

final class NotificacaoController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        $userId = (int) (user()['id'] ?? 0);
        if ($userId <= 0) {
            abort(403, 'Usuário não autenticado.');
        }
        $this->generateAlerts($userId);

        $filtro = (string) Request::get('filtro', 'nao_lidas');
        if (!in_array($filtro, ['nao_lidas', 'lidas', 'todas'], true)) {
            $filtro = 'nao_lidas';
        }
        $page = max(1, (int) Request::get('page', 1));
        $where = ['usuario_id = :uid'];
        $params = ['uid' => $userId];
        if ($filtro === 'nao_lidas') {
            $where[] = 'lida = 0';
        } elseif ($filtro === 'lidas') {
            $where[] = 'lida = 1';
        }
        $sqlWhere = ' WHERE ' . implode(' AND ', $where);
        $total = (int) Database::scalar('SELECT COUNT(*) FROM notificacoes' . $sqlWhere, $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $offset = ($page - 1) * self::PER_PAGE;
        $params['limit'] = self::PER_PAGE;
        $params['offset'] = $offset;
        $rows = Database::fetchAll(
            'SELECT id, titulo, mensagem, tipo, referencia, lida, data_evento, created_at
             FROM notificacoes' . $sqlWhere . '
             ORDER BY lida ASC, COALESCE(data_evento, created_at) DESC, id DESC
             LIMIT :limit OFFSET :offset',
            $params
        );
        $naoLidas = (int) Database::scalar('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = :uid AND lida = 0', ['uid' => $userId]);
        $this->render('notificacoes/index', [
            'title' => 'Notificações',
            'rows' => $rows,
            'filtro' => $filtro,
            'naoLidas' => $naoLidas,
            'paginator' => ['total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => self::PER_PAGE, 'offset' => $offset],
        ]);
    }

    public function markRead(): void
    {
        $userId = (int) (user()['id'] ?? 0);
        if ($userId <= 0) {
            abort(403, 'Usuário não autenticado.');
        }
        $todos = (string) Request::post('todos', Request::get('todos', '')) === '1';
        $id = (int) Request::post('id', Request::get('id', 0));
        if ($todos) {
            Database::update('notificacoes', ['lida' => 1], 'usuario_id = :uid AND lida = 0', ['uid' => $userId]);
            log_action('marcou_notificacoes_lidas', 'notificacoes', null, 'Todas as notificações marcadas como lidas.');
        } elseif ($id > 0) {
            Database::update('notificacoes', ['lida' => 1], 'id = :id AND usuario_id = :uid', ['id' => $id, 'uid' => $userId]);
            log_action('marcou_notificacao_lida', 'notificacoes', $id, 'Notificação marcada como lida.');
        }
        if (!Request::isAjax()) {
            flash('success', 'Notificação atualizada.');
        }
        $this->redirect('notificacoes.index');
    }

    private function generateAlerts(int $userId): void
    {
        $alerts = [];
        $alerts = array_merge($alerts, $this->consultasProximas());
        $alerts = array_merge($alerts, $this->retornosPendentes());
        $alerts = array_merge($alerts, $this->vacinasAlertas());
        $alerts = array_merge($alerts, $this->estoqueBaixo());
        $alerts = array_merge($alerts, $this->medicamentosValidade());
        $alerts = array_merge($alerts, $this->contasVencidas());
        $alerts = array_merge($alerts, $this->aniversariosAnimais());

        foreach ($alerts as $alert) {
            $ref = (string) ($alert['referencia'] ?? '');
            if ($ref === '') {
                continue;
            }
            $exists = Database::scalar(
                'SELECT id FROM notificacoes WHERE usuario_id = :uid AND referencia = :ref LIMIT 1',
                ['uid' => $userId, 'ref' => $ref]
            );
            if ($exists !== false && $exists !== null) {
                continue;
            }
            Database::insert('notificacoes', [
                'usuario_id' => $userId,
                'titulo' => mb_substr((string) $alert['titulo'], 0, 180),
                'mensagem' => (string) $alert['mensagem'],
                'tipo' => mb_substr((string) ($alert['tipo'] ?? 'info'), 0, 40),
                'referencia' => mb_substr($ref, 0, 120),
                'lida' => 0,
                'data_evento' => $alert['data_evento'] ?? null,
            ]);
        }
    }

    private function consultasProximas(): array
    {
        $rows = Database::fetchAll(
            'SELECT ag.id, ag.data_agendamento, ag.hora_inicio, c.nome AS cliente, a.nome AS animal
             FROM agendamentos ag
             INNER JOIN clientes c ON c.id = ag.cliente_id
             INNER JOIN animais a ON a.id = ag.animal_id
             WHERE ag.status IN ("aguardando","confirmado")
               AND CONCAT(ag.data_agendamento, " ", ag.hora_inicio) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
             ORDER BY ag.data_agendamento, ag.hora_inicio'
        );
        $alerts = [];
        foreach ($rows as $r) {
            $dt = (string) $r['data_agendamento'] . ' ' . substr((string) $r['hora_inicio'], 0, 5);
            $alerts[] = [
                'titulo' => 'Consulta próxima',
                'mensagem' => 'Consulta de ' . $r['animal'] . ' (' . $r['cliente'] . ') em ' . date_br($dt, true) . '.',
                'tipo' => 'agenda',
                'referencia' => 'agenda:' . $r['id'],
                'data_evento' => (string) $r['data_agendamento'] . ' ' . (string) $r['hora_inicio'],
            ];
        }
        return $alerts;
    }

    private function retornosPendentes(): array
    {
        $rows = Database::fetchAll(
            'SELECT p.id, p.animal_id, p.retorno_recomendado, a.nome AS animal, c.nome AS cliente
             FROM prontuarios p
             INNER JOIN animais a ON a.id = p.animal_id
             INNER JOIN clientes c ON c.id = a.cliente_id
             WHERE p.retorno_recomendado IS NOT NULL
               AND p.retorno_recomendado BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
               AND NOT EXISTS (
                   SELECT 1 FROM atendimentos at
                   WHERE at.animal_id = p.animal_id AND DATE(at.data_atendimento) >= p.retorno_recomendado
               )'
        );
        $alerts = [];
        foreach ($rows as $r) {
            $alerts[] = [
                'titulo' => 'Retorno pendente',
                'mensagem' => 'Retorno recomendado para ' . $r['animal'] . ' (' . $r['cliente'] . ') até ' . date_br((string) $r['retorno_recomendado']) . '.',
                'tipo' => 'retorno',
                'referencia' => 'retorno:' . $r['id'],
                'data_evento' => (string) $r['retorno_recomendado'] . ' 00:00:00',
            ];
        }
        return $alerts;
    }

    private function vacinasAlertas(): array
    {
        $rows = Database::fetchAll(
            'SELECT vc.id, vc.proxima_dose, a.nome AS animal, c.nome AS cliente, v.nome AS vacina
             FROM vacinacoes vc
             INNER JOIN animais a ON a.id = vc.animal_id
             INNER JOIN clientes c ON c.id = vc.cliente_id
             INNER JOIN vacinas v ON v.id = vc.vacina_id
             WHERE vc.proxima_dose IS NOT NULL
               AND vc.proxima_dose <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)'
        );
        $alerts = [];
        foreach ($rows as $r) {
            $atrasada = strtotime((string) $r['proxima_dose']) < strtotime(date('Y-m-d'));
            $alerts[] = [
                'titulo' => $atrasada ? 'Vacina atrasada' : 'Vacina próxima',
                'mensagem' => 'Vacina ' . $r['vacina'] . ' de ' . $r['animal'] . ' (' . $r['cliente'] . ') ' . ($atrasada ? 'atrasada desde ' : 'prevista para ') . date_br((string) $r['proxima_dose']) . '.',
                'tipo' => 'vacina',
                'referencia' => 'vacina:' . $r['id'] . ':' . $r['proxima_dose'],
                'data_evento' => (string) $r['proxima_dose'] . ' 00:00:00',
            ];
        }
        return $alerts;
    }

    private function estoqueBaixo(): array
    {
        $rows = Database::fetchAll('SELECT id, nome, quantidade, estoque_minimo FROM produtos WHERE quantidade <= estoque_minimo');
        $alerts = [];
        foreach ($rows as $r) {
            $alerts[] = [
                'titulo' => 'Estoque baixo',
                'mensagem' => 'Produto ' . $r['nome'] . ' com estoque ' . (int) $r['quantidade'] . ' (mínimo ' . (int) $r['estoque_minimo'] . ').',
                'tipo' => 'estoque',
                'referencia' => 'estoque:' . $r['id'] . ':' . $r['quantidade'],
                'data_evento' => date('Y-m-d H:i:s'),
            ];
        }
        return $alerts;
    }

    private function medicamentosValidade(): array
    {
        $rows = Database::fetchAll(
            'SELECT id, nome, data_validade FROM produtos
             WHERE data_validade IS NOT NULL AND data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
        );
        $alerts = [];
        foreach ($rows as $r) {
            $vencido = strtotime((string) $r['data_validade']) < strtotime(date('Y-m-d'));
            $alerts[] = [
                'titulo' => $vencido ? 'Produto vencido' : 'Produto próximo da validade',
                'mensagem' => 'Produto ' . $r['nome'] . ($vencido ? ' vencido em ' : ' vence em ') . date_br((string) $r['data_validade']) . '.',
                'tipo' => 'validade',
                'referencia' => 'validade:' . $r['id'] . ':' . $r['data_validade'],
                'data_evento' => (string) $r['data_validade'] . ' 00:00:00',
            ];
        }
        return $alerts;
    }

    private function contasVencidas(): array
    {
        $alerts = [];
        $receber = Database::fetchAll(
            'SELECT id, descricao, vencimento, valor FROM contas_receber
             WHERE status IN ("aberto","parcial","vencido") AND vencimento < CURDATE()'
        );
        foreach ($receber as $r) {
            $alerts[] = [
                'titulo' => 'Conta a receber vencida',
                'mensagem' => $r['descricao'] . ' vencida em ' . date_br((string) $r['vencimento']) . ' (' . money($r['valor']) . ').',
                'tipo' => 'financeiro',
                'referencia' => 'cr:' . $r['id'] . ':' . $r['vencimento'],
                'data_evento' => (string) $r['vencimento'] . ' 00:00:00',
            ];
        }
        $pagar = Database::fetchAll(
            'SELECT id, descricao, vencimento, valor FROM contas_pagar
             WHERE status IN ("aberto","parcial","vencido") AND vencimento < CURDATE()'
        );
        foreach ($pagar as $r) {
            $alerts[] = [
                'titulo' => 'Conta a pagar vencida',
                'mensagem' => $r['descricao'] . ' vencida em ' . date_br((string) $r['vencimento']) . ' (' . money($r['valor']) . ').',
                'tipo' => 'financeiro',
                'referencia' => 'cp:' . $r['id'] . ':' . $r['vencimento'],
                'data_evento' => (string) $r['vencimento'] . ' 00:00:00',
            ];
        }
        return $alerts;
    }

    private function aniversariosAnimais(): array
    {
        $rows = Database::fetchAll(
            'SELECT a.id, a.nome, c.nome AS cliente, a.data_nascimento
             FROM animais a
             INNER JOIN clientes c ON c.id = a.cliente_id
             WHERE a.ativo = 1 AND a.data_nascimento IS NOT NULL
               AND MONTH(a.data_nascimento) = MONTH(CURDATE()) AND DAY(a.data_nascimento) = DAY(CURDATE())'
        );
        $alerts = [];
        foreach ($rows as $r) {
            $alerts[] = [
                'titulo' => 'Aniversário de animal',
                'mensagem' => $r['nome'] . ' (' . $r['cliente'] . ') faz aniversário hoje.',
                'tipo' => 'aniversario',
                'referencia' => 'niver:' . $r['id'] . ':' . date('Y-m-d'),
                'data_evento' => date('Y-m-d') . ' 00:00:00',
            ];
        }
        return $alerts;
    }
}
