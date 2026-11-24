<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Core\Session;
use PetCare\Validators\Validator;

final class PortalController extends Controller
{
    public function login(): void
    {
        if (Session::get('portal_cliente_id')) {
            $this->redirect('portal.index');
        }
        $this->render('portal/login', [
            'title' => 'Acesso do tutor',
        ], 'portal');
    }

    public function authenticate(): void
    {
        $email = trim((string) Request::post('email', ''));
        $cpf = preg_replace('/\D+/', '', (string) Request::post('senha', '')) ?? '';
        $errors = [];
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Informe um e-mail válido.';
        }
        if ($cpf === '') {
            $errors['senha'] = 'Informe o CPF cadastrado.';
        }
        if ($errors !== []) {
            set_old(['email' => $email]);
            $this->render('portal/login', ['title' => 'Acesso do tutor', 'errors' => $errors], 'portal');
            return;
        }
        $cliente = Database::fetch(
            'SELECT id, nome, email, cpf, ativo FROM clientes WHERE email = :email LIMIT 1',
            ['email' => $email]
        );
        $cpfDb = preg_replace('/\D+/', '', (string) ($cliente['cpf'] ?? '')) ?? '';
        if ($cliente === null || (int) ($cliente['ativo'] ?? 0) !== 1 || $cpfDb === '' || !hash_equals($cpfDb, $cpf)) {
            set_old(['email' => $email]);
            flash('error', 'Dados de acesso inválidos.');
            $this->redirect('portal.login');
        }
        Session::regenerate();
        Session::set('portal_cliente_id', (int) $cliente['id']);
        Session::set('portal_cliente_nome', (string) $cliente['nome']);
        clear_old();
        $this->redirect('portal.index');
    }

    public function logout(): void
    {
        Session::forget('portal_cliente_id');
        Session::forget('portal_cliente_nome');
        flash('success', 'Você saiu do portal.');
        $this->redirect('portal.login');
    }

    public function index(): void
    {
        $cliente = $this->cliente();
        $clienteId = (int) $cliente['id'];
        $animais = Database::fetchAll(
            'SELECT a.id, a.nome, a.foto, e.nome AS especie, r.nome AS raca, a.data_nascimento
             FROM animais a
             LEFT JOIN especies e ON e.id = a.especie_id
             LEFT JOIN racas r ON r.id = a.raca_id
             WHERE a.cliente_id = :id AND a.ativo = 1
             ORDER BY a.nome ASC',
            ['id' => $clienteId]
        );
        $agendamentos = Database::fetchAll(
            'SELECT ag.id, ag.data_agendamento, ag.hora_inicio, ag.tipo, ag.status, a.nome AS animal, v.nome AS veterinario
             FROM agendamentos ag
             INNER JOIN animais a ON a.id = ag.animal_id
             LEFT JOIN veterinarios v ON v.id = ag.veterinario_id
             WHERE ag.cliente_id = :id AND ag.data_agendamento >= CURDATE() AND ag.status NOT IN ("cancelado","falta","finalizado")
             ORDER BY ag.data_agendamento ASC, ag.hora_inicio ASC
             LIMIT 5',
            ['id' => $clienteId]
        );
        $vacinas = Database::fetchAll(
            'SELECT vc.proxima_dose, a.nome AS animal, va.nome AS vacina
             FROM vacinacoes vc
             INNER JOIN animais a ON a.id = vc.animal_id
             INNER JOIN vacinas va ON va.id = vc.vacina_id
             WHERE vc.cliente_id = :id AND vc.proxima_dose IS NOT NULL AND vc.proxima_dose <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY vc.proxima_dose ASC
             LIMIT 5',
            ['id' => $clienteId]
        );
        $saldo = (float) Database::scalar(
            'SELECT COALESCE(SUM(valor - valor_pago - desconto),0) FROM contas_receber
             WHERE cliente_id = :id AND status IN ("aberto","parcial","vencido")',
            ['id' => $clienteId]
        );
        $this->render('portal/index', [
            'title' => 'Portal do Tutor',
            'cliente' => $cliente,
            'animais' => $animais,
            'agendamentos' => $agendamentos,
            'vacinas' => $vacinas,
            'saldo' => $saldo,
        ], 'portal');
    }

    public function animais(): void
    {
        $cliente = $this->cliente();
        $rows = Database::fetchAll(
            'SELECT a.id, a.nome, a.foto, a.data_nascimento, a.sexo, e.nome AS especie, r.nome AS raca, a.peso
             FROM animais a
             LEFT JOIN especies e ON e.id = a.especie_id
             LEFT JOIN racas r ON r.id = a.raca_id
             WHERE a.cliente_id = :id AND a.ativo = 1
             ORDER BY a.nome ASC',
            ['id' => (int) $cliente['id']]
        );
        $this->render('portal/animais', ['title' => 'Meus animais', 'rows' => $rows], 'portal');
    }

    public function agendamentos(): void
    {
        $cliente = $this->cliente();
        $rows = Database::fetchAll(
            'SELECT ag.id, ag.codigo, ag.data_agendamento, ag.hora_inicio, ag.hora_fim, ag.tipo, ag.status, ag.observacoes,
                    a.nome AS animal, v.nome AS veterinario
             FROM agendamentos ag
             INNER JOIN animais a ON a.id = ag.animal_id
             LEFT JOIN veterinarios v ON v.id = ag.veterinario_id
             WHERE ag.cliente_id = :id
             ORDER BY ag.data_agendamento DESC, ag.hora_inicio DESC',
            ['id' => (int) $cliente['id']]
        );
        $this->render('portal/agendamentos', ['title' => 'Meus agendamentos', 'rows' => $rows], 'portal');
    }

    public function solicitar(): void
    {
        $cliente = $this->cliente();
        $this->render('portal/solicitar', [
            'title' => 'Solicitar agendamento',
            'animais' => $this->animaisCliente((int) $cliente['id']),
            'veterinarios' => Database::fetchAll('SELECT id, nome FROM veterinarios WHERE ativo = 1 ORDER BY nome ASC'),
            'values' => $this->withOld($this->agendamentoDefaults()),
            'errors' => [],
        ], 'portal');
    }

    public function storeAgendamento(): void
    {
        $cliente = $this->cliente();
        $clienteId = (int) $cliente['id'];
        $data = $this->collectAgendamento();
        $errors = (new Validator($this->agendamentoRules()))->validate($data);

        $animal = Database::fetch('SELECT id FROM animais WHERE id = :id AND cliente_id = :cid AND ativo = 1 LIMIT 1', ['id' => (int) $data['animal_id'], 'cid' => $clienteId]);
        if ($animal === null) {
            $errors['animal_id'] = 'Selecione um animal válido.';
        }
        $vet = Database::fetch('SELECT id FROM veterinarios WHERE id = :id AND ativo = 1 LIMIT 1', ['id' => (int) $data['veterinario_id']]);
        if ($vet === null) {
            $errors['veterinario_id'] = 'Selecione um veterinário válido.';
        }

        $horaFim = '';
        $inicioTs = strtotime((string) $data['data_agendamento'] . ' ' . (string) $data['hora_inicio']);
        $duracao = (int) $data['duracao_minutos'];
        if ($inicioTs === false || $duracao <= 0) {
            $errors['hora_inicio'] = 'Data/hora inválida.';
        } else {
            $horaFim = date('H:i:s', strtotime('+' . $duracao . ' minutes', $inicioTs));
            $data['hora_inicio'] = date('H:i:s', $inicioTs);
        }

        if ($errors === [] && $horaFim !== '') {
            $conflict = Database::fetch(
                'SELECT id FROM agendamentos
                 WHERE veterinario_id = :vet AND data_agendamento = :data AND status NOT IN ("cancelado","falta")
                   AND hora_inicio < :fim AND hora_fim > :inicio
                 LIMIT 1',
                [
                    'vet' => (int) $data['veterinario_id'],
                    'data' => $data['data_agendamento'],
                    'inicio' => $data['hora_inicio'],
                    'fim' => $horaFim,
                ]
            );
            if ($conflict !== null) {
                $errors['hora_inicio'] = 'Horário indisponível para o veterinário selecionado.';
            }
        }

        if ($errors !== []) {
            set_old($data);
            $this->render('portal/solicitar', [
                'title' => 'Solicitar agendamento',
                'animais' => $this->animaisCliente($clienteId),
                'veterinarios' => Database::fetchAll('SELECT id, nome FROM veterinarios WHERE ativo = 1 ORDER BY nome ASC'),
                'values' => $data,
                'errors' => $errors,
            ], 'portal');
            return;
        }

        $id = Database::insert('agendamentos', [
            'codigo' => gerar_codigo('AG', 'agendamentos'),
            'cliente_id' => $clienteId,
            'animal_id' => (int) $data['animal_id'],
            'veterinario_id' => (int) $data['veterinario_id'],
            'tipo' => $data['tipo'],
            'data_agendamento' => $data['data_agendamento'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fim' => $horaFim,
            'duracao_minutos' => $duracao,
            'valor' => 0,
            'observacoes' => $data['observacoes'] !== '' ? $data['observacoes'] : null,
            'status' => 'aguardando',
        ]);
        log_action('solicitou_agendamento_portal', 'portal', $id, 'Agendamento solicitado pelo tutor #' . $clienteId);
        clear_old();
        flash('success', 'Agendamento solicitado com sucesso.');
        $this->redirect('portal.agendamentos');
    }

    public function vacinas(): void
    {
        $cliente = $this->cliente();
        $clienteId = (int) $cliente['id'];
        $animalId = (int) Request::get('animal_id', 0);
        $params = ['id' => $clienteId];
        $where = 'vc.cliente_id = :id';
        if ($animalId > 0) {
            $where .= ' AND vc.animal_id = :animal_id';
            $params['animal_id'] = $animalId;
        }
        $rows = Database::fetchAll(
            'SELECT vc.data_aplicacao, vc.proxima_dose, vc.lote, vc.observacoes,
                    a.nome AS animal, va.nome AS vacina, v.nome AS veterinario
             FROM vacinacoes vc
             INNER JOIN animais a ON a.id = vc.animal_id
             INNER JOIN vacinas va ON va.id = vc.vacina_id
             LEFT JOIN veterinarios v ON v.id = vc.veterinario_id
             WHERE ' . $where . '
             ORDER BY vc.data_aplicacao DESC, vc.id DESC',
            $params
        );
        $this->render('portal/vacinas', [
            'title' => 'Vacinas',
            'rows' => $rows,
            'animais' => $this->animaisCliente($clienteId),
            'animalId' => $animalId,
        ], 'portal');
    }

    public function receitas(): void
    {
        $cliente = $this->cliente();
        $clienteId = (int) $cliente['id'];
        $receitas = Database::fetchAll(
            'SELECT r.id, r.codigo, r.data_prescricao, r.orientacoes, r.controle_especial,
                    a.nome AS animal, v.nome AS veterinario
             FROM receitas r
             INNER JOIN animais a ON a.id = r.animal_id
             INNER JOIN veterinarios v ON v.id = r.veterinario_id
             WHERE r.cliente_id = :id
             ORDER BY r.data_prescricao DESC, r.id DESC',
            ['id' => $clienteId]
        );
        foreach ($receitas as &$r) {
            $r['itens'] = Database::fetchAll(
                'SELECT medicamento, dosagem, via_administracao, frequencia, duracao, observacoes
                 FROM receita_itens WHERE receita_id = :id ORDER BY id ASC',
                ['id' => (int) $r['id']]
            );
        }
        unset($r);
        $this->render('portal/receitas', ['title' => 'Receitas', 'rows' => $receitas], 'portal');
    }

    public function exames(): void
    {
        $cliente = $this->cliente();
        $rows = Database::fetchAll(
            'SELECT e.codigo, e.tipo, e.data_solicitacao, e.data_resultado, e.status, e.resultados, e.observacoes,
                    a.nome AS animal, v.nome AS veterinario
             FROM exames e
             INNER JOIN animais a ON a.id = e.animal_id
             LEFT JOIN veterinarios v ON v.id = e.veterinario_id
             WHERE e.cliente_id = :id AND e.status = "liberado"
             ORDER BY e.data_resultado DESC, e.data_solicitacao DESC',
            ['id' => (int) $cliente['id']]
        );
        $this->render('portal/exames', ['title' => 'Exames', 'rows' => $rows], 'portal');
    }

    public function pagamentos(): void
    {
        $cliente = $this->cliente();
        $clienteId = (int) $cliente['id'];
        $contas = Database::fetchAll(
            'SELECT cr.id, cr.codigo, cr.descricao, cr.valor, cr.valor_pago, cr.desconto, cr.vencimento, cr.status
             FROM contas_receber cr
             WHERE cr.cliente_id = :id
             ORDER BY cr.vencimento DESC, cr.id DESC',
            ['id' => $clienteId]
        );
        $pagamentos = Database::fetchAll(
            'SELECT p.data_pagamento, p.valor, p.forma_pagamento, p.observacoes, cr.codigo AS conta_codigo, cr.descricao
             FROM pagamentos p
             LEFT JOIN contas_receber cr ON cr.id = p.conta_receber_id
             WHERE p.cliente_id = :id
             ORDER BY p.data_pagamento DESC, p.id DESC',
            ['id' => $clienteId]
        );
        $this->render('portal/pagamentos', ['title' => 'Pagamentos', 'contas' => $contas, 'pagamentos' => $pagamentos], 'portal');
    }

    public function perfil(): void
    {
        $cliente = $this->cliente();
        $this->render('portal/perfil', [
            'title' => 'Meu perfil',
            'values' => $this->withOld($this->perfilValues($cliente)),
            'errors' => [],
        ], 'portal');
    }

    public function updatePerfil(): void
    {
        $cliente = $this->cliente();
        $id = (int) $cliente['id'];
        $data = $this->collectPerfil();
        $rules = [
            'nome' => 'required|min:3|max:180',
            'email' => 'nullable|email|max:255|unique:clientes,email,id',
            'telefone' => 'nullable|max:20',
            'whatsapp' => 'nullable|max:20',
            'endereco' => 'nullable|max:255',
            'numero' => 'nullable|max:20',
            'complemento' => 'nullable|max:120',
            'bairro' => 'nullable|max:120',
            'cidade' => 'nullable|max:120',
            'estado' => 'nullable|max:2',
            'cep' => 'nullable|max:10',
        ];
        $errors = (new Validator($rules))->validate($data + ['id' => $id]);
        if ($errors !== []) {
            set_old($data);
            $this->render('portal/perfil', ['title' => 'Meu perfil', 'values' => $data, 'errors' => $errors], 'portal');
            return;
        }
        Database::update('clientes', $data, 'id = :id', ['id' => $id]);
        log_action('atualizou_perfil_portal', 'portal', $id, 'Perfil do tutor atualizado.');
        clear_old();
        flash('success', 'Perfil atualizado com sucesso.');
        $this->redirect('portal.perfil');
    }

    private function cliente(): array
    {
        $id = (int) Session::get('portal_cliente_id', 0);
        if ($id <= 0) {
            $this->redirect('portal.login');
        }
        $cliente = Database::fetch('SELECT * FROM clientes WHERE id = :id AND ativo = 1 LIMIT 1', ['id' => $id]);
        if ($cliente === null) {
            Session::forget('portal_cliente_id');
            Session::forget('portal_cliente_nome');
            $this->redirect('portal.login');
        }
        return $cliente;
    }

    private function animaisCliente(int $clienteId): array
    {
        return Database::fetchAll('SELECT id, nome FROM animais WHERE cliente_id = :id AND ativo = 1 ORDER BY nome ASC', ['id' => $clienteId]);
    }

    private function agendamentoDefaults(): array
    {
        return ['animal_id' => '', 'veterinario_id' => '', 'tipo' => 'Consulta', 'data_agendamento' => date('Y-m-d'), 'hora_inicio' => '', 'duracao_minutos' => 30, 'observacoes' => ''];
    }

    private function collectAgendamento(): array
    {
        $data = [];
        foreach ($this->agendamentoDefaults() as $key => $default) {
            $value = Request::post($key, $default);
            $data[$key] = is_string($value) ? trim($value) : $value;
        }
        $data['duracao_minutos'] = (int) $data['duracao_minutos'];
        return $data;
    }

    private function agendamentoRules(): array
    {
        return [
            'animal_id' => 'required|integer',
            'veterinario_id' => 'required|integer',
            'tipo' => 'required|in:Consulta,Retorno,Vacinação,Exame,Cirurgia,Banho e tosa',
            'data_agendamento' => 'required|date',
            'hora_inicio' => 'required',
            'duracao_minutos' => 'required|integer',
        ];
    }

    private function perfilValues(array $cliente): array
    {
        return [
            'nome' => (string) ($cliente['nome'] ?? ''),
            'telefone' => (string) ($cliente['telefone'] ?? ''),
            'whatsapp' => (string) ($cliente['whatsapp'] ?? ''),
            'email' => (string) ($cliente['email'] ?? ''),
            'endereco' => (string) ($cliente['endereco'] ?? ''),
            'numero' => (string) ($cliente['numero'] ?? ''),
            'complemento' => (string) ($cliente['complemento'] ?? ''),
            'bairro' => (string) ($cliente['bairro'] ?? ''),
            'cidade' => (string) ($cliente['cidade'] ?? ''),
            'estado' => (string) ($cliente['estado'] ?? ''),
            'cep' => (string) ($cliente['cep'] ?? ''),
        ];
    }

    private function collectPerfil(): array
    {
        $data = [];
        foreach (array_keys($this->perfilValues([])) as $key) {
            $value = Request::post($key, '');
            $data[$key] = is_string($value) ? trim($value) : $value;
        }
        foreach (['telefone','whatsapp','email','endereco','numero','complemento','bairro','cidade','estado','cep'] as $nullable) {
            if ($data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }
        return $data;
    }

    private function withOld(array $defaults): array
    {
        $values = [];
        foreach ($defaults as $key => $default) {
            $values[$key] = old($key, $default);
        }
        clear_old();
        return $values;
    }
}
