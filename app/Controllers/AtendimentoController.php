<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Services\AtendimentoService;
use PetCare\Validators\Validator;

final class AtendimentoController extends Controller
{
    private const PER_PAGE = 15;

    private const STATUS = [
        'finalizado' => 'Finalizado',
        'em_andamento' => 'Em andamento',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
        'pendente' => 'Pendente',
        'aguardando' => 'Aguardando',
    ];

    public function index(): void
    {
        $importados = AtendimentoService::syncFinalizados();
        if ($importados > 0) {
            flash('info', $importados === 1
                ? '1 atendimento finalizado foi importado da agenda.'
                : $importados . ' atendimentos finalizados foram importados da agenda.');
        }

        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $status = (string) Request::get('status', '');
        if (!array_key_exists($status, self::STATUS)) {
            $status = '';
        }

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(a.nome LIKE :q OR c.nome LIKE :q OR v.nome LIKE :q OR at.codigo LIKE :q OR at.queixa_principal LIKE :q OR at.status LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($status !== '') {
            $where[] = 'at.status = :status';
            $params['status'] = $status;
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar(
             'SELECT COUNT(*) FROM atendimentos at
              INNER JOIN animais a ON a.id = at.animal_id
              INNER JOIN clientes c ON c.id = a.cliente_id
              LEFT JOIN veterinarios v ON v.id = at.veterinario_id' . $sqlWhere,
            $params
        );
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT at.id, at.codigo, at.data_atendimento, at.queixa_principal, at.status, at.valor,
                    a.nome AS pet, c.nome AS tutor, v.nome AS veterinario,
                    (SELECT p.id FROM prontuarios p WHERE p.atendimento_id = at.id ORDER BY p.id DESC LIMIT 1) AS prontuario_id
             FROM atendimentos at
             INNER JOIN animais a ON a.id = at.animal_id
             INNER JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN veterinarios v ON v.id = at.veterinario_id' . $sqlWhere . '
             ORDER BY at.data_atendimento DESC, at.id DESC
             LIMIT :limit OFFSET :offset',
            $params
        );

        $extraFilters = '<div class="col-md-3 col-lg-2"><label for="status" class="form-label small fw-semibold">Status</label><select name="status" id="status" class="form-select"><option value="">Todos</option>';
        foreach (self::STATUS as $value => $label) {
            $extraFilters .= '<option value="' . e($value) . '"' . selected($status, (string) $value) . '>' . e($label) . '</option>';
        }
        $extraFilters .= '</select></div>';

        $actions = [
            ['label' => 'Visualizar', 'route' => 'atendimentos.show', 'icon' => 'eye', 'class' => 'btn-outline-primary'],
            ['label' => 'Editar prontuário', 'route' => 'prontuarios.show', 'icon' => 'file-pen', 'class' => 'btn-outline-secondary', 'prontuario' => true],
        ];
        if (can('atendimentos', 'edit')) {
            $actions[] = ['label' => 'Editar', 'route' => 'atendimentos.edit', 'icon' => 'pen', 'class' => 'btn-outline-primary'];
        }
        if (can('atendimentos', 'delete')) {
            $actions[] = ['label' => 'Excluir', 'route' => 'atendimentos.destroy', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Excluir este atendimento? Esta ação não pode ser desfeita.'];
        }

        $this->render('atendimentos/index', [
            'title' => 'Atendimentos',
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'actions' => $actions,
        ]);
    }

    public function create(): void
    {
        $agendamentoId = (int) Request::get('agendamento_id', 0);
        $defaults = $this->defaults();
        $clienteNome = '';

        if ($agendamentoId > 0) {
            $agendamento = Database::fetch(
                'SELECT ag.*, a.nome AS pet, c.nome AS cliente
                 FROM agendamentos ag
                 INNER JOIN animais a ON a.id = ag.animal_id
                 INNER JOIN clientes c ON c.id = ag.cliente_id
                 WHERE ag.id = :id LIMIT 1',
                ['id' => $agendamentoId]
            );
            if ($agendamento === null) {
                flash('error', 'Agendamento não encontrado.');
                $this->redirect('agenda.index');
            }
            $defaults['agendamento_id'] = $agendamentoId;
            $defaults['animal_id'] = (int) $agendamento['animal_id'];
            $defaults['veterinario_id'] = (int) $agendamento['veterinario_id'];
            $defaults['valor'] = number_format((float) $agendamento['valor'], 2, '.', '');
            $defaults['queixa_principal'] = (string) ($agendamento['observacoes'] ?? '');
            $defaults['data_atendimento'] = date('Y-m-d\TH:i');
            $clienteNome = (string) ($agendamento['cliente'] ?? '');
        }

        $this->renderForm('Novo atendimento', $this->withOld($defaults), [], $clienteNome);
    }

    public function store(): void
    {
        $data = $this->collect();
        if (($data['codigo'] ?? '') === '') {
            $data['codigo'] = gerar_codigo('ATD', 'atendimentos');
        }
        $errors = $this->validate($data);
        $gerarProntuario = Request::has('gerar_prontuario') && (int) Request::post('gerar_prontuario', 0) === 1;

        if ($errors !== []) {
            $values = $data + ['gerar_prontuario' => $gerarProntuario ? 1 : 0];
            $values['data_atendimento'] = $this->toLocal((string) $values['data_atendimento']);
            set_old($values);
            $this->renderForm('Novo atendimento', $values, $errors, $this->clienteNomePorAnimal((int) $data['animal_id']));
            return;
        }

        $clienteId = Database::scalar('SELECT cliente_id FROM animais WHERE id = :id LIMIT 1', ['id' => (int) $data['animal_id']]);
        if ($clienteId === false) {
            $errors['animal_id'] = 'Animal não encontrado.';
            $values = $data + ['gerar_prontuario' => $gerarProntuario ? 1 : 0];
            $values['data_atendimento'] = $this->toLocal((string) $values['data_atendimento']);
            set_old($values);
            $this->renderForm('Novo atendimento', $values, $errors, '');
            return;
        }

        $agendamentoId = (int) $data['agendamento_id'];
        $valor = (float) $data['valor'];

        $id = Database::transaction(static function () use ($data, $clienteId, $agendamentoId, $gerarProntuario, $valor): int {
            $atendimentoId = Database::insert('atendimentos', [
                'codigo' => $data['codigo'],
                'agendamento_id' => $agendamentoId > 0 ? $agendamentoId : null,
                'animal_id' => (int) $data['animal_id'],
                'veterinario_id' => (int) $data['veterinario_id'],
                'data_atendimento' => $data['data_atendimento'],
                'queixa_principal' => $data['queixa_principal'],
                'status' => $data['status'],
                'valor' => number_format($valor, 2, '.', ''),
            ]);

            if ($agendamentoId > 0) {
                Database::update('agendamentos', ['status' => 'finalizado'], 'id = :id', ['id' => $agendamentoId]);
            }

            if ($gerarProntuario) {
                Database::insert('prontuarios', [
                    'atendimento_id' => $atendimentoId,
                    'animal_id' => (int) $data['animal_id'],
                    'veterinario_id' => (int) $data['veterinario_id'],
                    'data_atendimento' => $data['data_atendimento'],
                    'anamnese' => $data['anamnese'],
                    'exame_fisico' => $data['exame_fisico'],
                    'temperatura' => $data['temperatura'],
                    'frequencia_cardiaca' => $data['frequencia_cardiaca'],
                    'frequencia_respiratoria' => $data['frequencia_respiratoria'],
                    'peso' => $data['peso'],
                    'diagnostico' => $data['diagnostico'],
                    'procedimentos' => $data['procedimentos'],
                    'medicamentos' => $data['medicamentos'],
                    'dosagem' => $data['dosagem'],
                    'frequencia' => $data['frequencia'],
                    'duracao_tratamento' => $data['duracao_tratamento'],
                    'recomendacoes' => $data['recomendacoes'],
                    'retorno_recomendado' => $data['retorno_recomendado'],
                    'finalizado' => 1,
                ]);
            }

            if ($valor > 0) {
                $existing = Database::scalar(
                    "SELECT 1 FROM contas_receber WHERE origem = 'consulta' AND referencia_id = :id LIMIT 1",
                    ['id' => $atendimentoId]
                );
                if ($existing === false) {
                    Database::insert('contas_receber', [
                        'codigo' => gerar_codigo('CR', 'contas_receber'),
                        'cliente_id' => (int) $clienteId,
                        'origem' => 'consulta',
                        'referencia_id' => $atendimentoId,
                        'descricao' => 'Atendimento ' . $data['codigo'],
                        'valor' => number_format($valor, 2, '.', ''),
                        'valor_pago' => 0,
                        'desconto' => 0,
                        'vencimento' => date('Y-m-d'),
                        'status' => 'aberto',
                    ]);
                }
            }

            return $atendimentoId;
        });

        log_action('registrou_atendimento', 'atendimentos', $id, 'Atendimento ' . $data['codigo'] . ' registrado.');
        clear_old();
        flash('success', 'Atendimento registrado com sucesso.');
        $this->redirectTo('atendimentos.show', ['id' => $id]);
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) Request::get('id', 0);
        $atendimento = Database::fetch('SELECT * FROM atendimentos WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($atendimento === null) {
            abort(404, 'Atendimento não encontrado.');
        }

        $values = $this->defaults();
        foreach ($values as $key => $default) {
            if (array_key_exists($key, $atendimento)) {
                $values[$key] = $atendimento[$key];
            }
        }
        $values['id'] = $id;
        $values['data_atendimento'] = $this->toLocal((string) $atendimento['data_atendimento']);
        $values['gerar_prontuario'] = Database::scalar('SELECT 1 FROM prontuarios WHERE atendimento_id = :id LIMIT 1', ['id' => $id]) !== false ? 1 : 0;

        $this->renderForm(
            'Editar atendimento',
            $this->withOld($values),
            [],
            $this->clienteNomePorAnimal((int) $values['animal_id']),
            'atendimentos.update',
            'Salvar alterações'
        );
    }

    public function update(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        if ($id <= 0 || (int) Database::scalar('SELECT COUNT(*) FROM atendimentos WHERE id = :id', ['id' => $id]) === 0) {
            abort(404, 'Atendimento não encontrado.');
        }

        $data = $this->collect();
        $data['id'] = $id;
        $errors = $this->validate($data, $id);
        if ($errors !== []) {
            $values = $data;
            $values['data_atendimento'] = $this->toLocal((string) $values['data_atendimento']);
            set_old($values);
            $this->renderForm(
                'Editar atendimento',
                $this->withOld($values),
                $errors,
                $this->clienteNomePorAnimal((int) $data['animal_id']),
                'atendimentos.update',
                'Salvar alterações'
            );
            return;
        }

        Database::transaction(static function () use ($id, $data): void {
            Database::update('atendimentos', [
                'codigo' => $data['codigo'],
                'agendamento_id' => (int) $data['agendamento_id'] > 0 ? (int) $data['agendamento_id'] : null,
                'animal_id' => (int) $data['animal_id'],
                'veterinario_id' => (int) $data['veterinario_id'],
                'data_atendimento' => $data['data_atendimento'],
                'queixa_principal' => $data['queixa_principal'],
                'status' => $data['status'],
                'valor' => number_format((float) $data['valor'], 2, '.', ''),
            ], 'id = :id', ['id' => $id]);

            Database::update('prontuarios', [
                'data_atendimento' => $data['data_atendimento'],
                'animal_id' => (int) $data['animal_id'],
                'veterinario_id' => (int) $data['veterinario_id'],
            ], 'atendimento_id = :id', ['id' => $id]);
        });

        log_action('editou_atendimento', 'atendimentos', $id, 'Atendimento ' . $data['codigo'] . ' atualizado.');
        clear_old();
        flash('success', 'Atendimento atualizado com sucesso.');
        $this->redirectTo('atendimentos.show', ['id' => $id]);
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? (int) Request::get('id', 0);
        $atendimento = Database::fetch(
            'SELECT at.*, a.nome AS pet, c.id AS cliente_id, c.nome AS tutor, v.nome AS veterinario,
                    ag.codigo AS agendamento_codigo,
                    (SELECT p.id FROM prontuarios p WHERE p.atendimento_id = at.id ORDER BY p.id DESC LIMIT 1) AS prontuario_id
             FROM atendimentos at
             INNER JOIN animais a ON a.id = at.animal_id
             INNER JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN veterinarios v ON v.id = at.veterinario_id
             LEFT JOIN agendamentos ag ON ag.id = at.agendamento_id
             WHERE at.id = :id LIMIT 1',
            ['id' => $id]
        );
        if ($atendimento === null) {
            abort(404, 'Atendimento não encontrado.');
        }

        $prontuario = null;
        if ((int) ($atendimento['prontuario_id'] ?? 0) > 0) {
            $prontuario = Database::fetch(
                'SELECT p.id, p.data_atendimento, p.anamnese, p.exame_fisico, p.diagnostico, p.procedimentos,
                        p.medicamentos, p.finalizado, v.nome AS veterinario
                 FROM prontuarios p LEFT JOIN veterinarios v ON v.id = p.veterinario_id
                 WHERE p.id = :id LIMIT 1',
                ['id' => (int) $atendimento['prontuario_id']]
            );
        }

        $conta = Database::fetch(
            "SELECT * FROM contas_receber WHERE origem = 'consulta' AND referencia_id = :id ORDER BY id DESC LIMIT 1",
            ['id' => $id]
        );

        $receita = Database::fetch(
            'SELECT id, codigo FROM receitas WHERE animal_id = :animal AND data_prescricao = :dia ORDER BY id DESC LIMIT 1',
            ['animal' => (int) $atendimento['animal_id'], 'dia' => substr((string) $atendimento['data_atendimento'], 0, 10)]
        );

        $sections = [
            'Atendimento' => [
                'Código' => $atendimento['codigo'],
                'Pet' => $atendimento['pet'],
                'Tutor' => $atendimento['tutor'],
                'Veterinário' => $atendimento['veterinario'],
                'Data' => date_br((string) $atendimento['data_atendimento'], true),
                'Agendamento' => $atendimento['agendamento_codigo'] ?: '—',
                'Queixa principal' => $atendimento['queixa_principal'],
                'Valor' => static fn(): string => e(money($atendimento['valor'] ?? 0)),
                'Status' => static fn(): string => status_badge($atendimento['status'] ?? null),
            ],
        ];

        if ($prontuario !== null) {
            $sections['Prontuário vinculado'] = [
                'Registro' => '#' . $prontuario['id'],
                'Data' => date_br((string) $prontuario['data_atendimento'], true),
                'Veterinário' => $prontuario['veterinario'],
                'Anamnese' => $prontuario['anamnese'],
                'Exame físico' => $prontuario['exame_fisico'],
                'Diagnóstico' => $prontuario['diagnostico'],
                'Procedimentos' => $prontuario['procedimentos'],
                'Medicação' => $prontuario['medicamentos'],
                'Situação' => static fn(): string => (int) ($prontuario['finalizado'] ?? 0) === 1 ? status_badge('finalizado') : status_badge('aguardando'),
            ];
        }

        if ($conta !== null) {
            $sections['Conta a receber'] = [
                'Código' => $conta['codigo'],
                'Descrição' => $conta['descricao'],
                'Valor' => static fn(): string => e(money($conta['valor'] ?? 0)),
                'Vencimento' => date_br((string) $conta['vencimento']),
                'Situação' => static fn(): string => status_badge($conta['status'] ?? null),
            ];
        }

        $actions = [
            $prontuario === null
                ? ['label' => 'Criar prontuário', 'route' => 'prontuarios.create', 'icon' => 'file-medical-plus', 'class' => 'btn-outline-primary']
                : ['label' => 'Editar prontuário', 'route' => 'prontuarios.edit', 'icon' => 'file-pen', 'class' => 'btn-outline-primary'],
            ['label' => 'Voltar', 'route' => 'atendimentos.index', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary'],
        ];
        if (can('atendimentos', 'edit')) {
            $actions[] = ['label' => 'Editar', 'route' => 'atendimentos.edit', 'icon' => 'pen', 'class' => 'btn-outline-primary'];
        }
        if ($receita !== null) {
            $actions[] = ['label' => 'Imprimir receita', 'route' => 'receitas.print', 'icon' => 'print', 'class' => 'btn-outline-success'];
        }

        $this->render('atendimentos/show', [
            'title' => 'Detalhes do atendimento',
            'atendimento' => $atendimento,
            'prontuario' => $prontuario,
            'conta' => $conta,
            'receita' => $receita,
            'sections' => $sections,
            'actions' => $actions,
            'recordId' => $id,
        ]);
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        if ($id <= 0 || (int) Database::scalar('SELECT COUNT(*) FROM atendimentos WHERE id = :id', ['id' => $id]) === 0) {
            abort(404, 'Atendimento não encontrado.');
        }

        Database::transaction(static fn(): int => Database::delete('atendimentos', 'id = :id', ['id' => $id]));
        log_action('excluiu_atendimento', 'atendimentos', $id, 'Atendimento excluído.');
        clear_old();
        flash('success', 'Atendimento excluído com sucesso.');
        $this->redirect('atendimentos.index');
    }

    private function renderForm(
        string $title,
        array $values,
        array $errors,
        string $clienteNome,
        string $action = 'atendimentos.store',
        string $submitLabel = 'Registrar atendimento'
    ): void
    {
        $this->render('atendimentos/form', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => $action,
            'cancelRoute' => 'atendimentos.index',
            'submitLabel' => $submitLabel,
            'fields' => $this->fields(),
            'clienteNome' => $clienteNome,
        ]);
    }

    private function fields(): array
    {
        $animais = [];
        foreach (Database::fetchAll(
            'SELECT a.id, a.nome, c.nome AS tutor FROM animais a
             INNER JOIN clientes c ON c.id = a.cliente_id
             WHERE a.ativo = 1 ORDER BY a.nome ASC'
        ) as $row) {
            $animais[(int) $row['id']] = $row['nome'] . ' · ' . $row['tutor'];
        }
        $veterinarios = [];
        foreach (Database::fetchAll('SELECT id, nome FROM veterinarios WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $veterinarios[(int) $row['id']] = $row['nome'];
        }

        return [
            'id' => ['label' => 'ID', 'type' => 'text', 'col' => 'd-none'],
            'agendamento_id' => ['label' => 'Agendamento', 'type' => 'text', 'col' => 'd-none'],
            'codigo' => ['label' => 'Código', 'type' => 'text', 'rules' => 'required|max:50', 'col' => 'col-md-4'],
            'animal_id' => ['label' => 'Animal (com tutor)', 'type' => 'select', 'rules' => 'required|integer', 'options' => $animais, 'col' => 'col-md-4'],
            'veterinario_id' => ['label' => 'Veterinário', 'type' => 'select', 'rules' => 'required|integer', 'options' => $veterinarios, 'col' => 'col-md-4'],
            'data_atendimento' => ['label' => 'Data e hora do atendimento', 'type' => 'text', 'rules' => 'required|datetime', 'col' => 'col-md-4'],
            'status' => ['label' => 'Status', 'type' => 'select', 'rules' => 'required', 'options' => self::STATUS, 'col' => 'col-md-4'],
            'valor' => ['label' => 'Valor (R$)', 'type' => 'number', 'rules' => 'required|numeric', 'col' => 'col-md-4'],
            'queixa_principal' => ['label' => 'Queixa principal', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'gerar_prontuario' => ['label' => 'Gerar prontuário', 'type' => 'checkbox', 'col' => 'col-md-4'],
            'anamnese' => ['label' => 'Anamnese', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12 clinical-field', 'rows' => 3],
            'exame_fisico' => ['label' => 'Exame físico', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12 clinical-field', 'rows' => 3],
            'temperatura' => ['label' => 'Temperatura (°C)', 'type' => 'number', 'rules' => 'nullable|numeric', 'col' => 'col-md-3 clinical-field'],
            'frequencia_cardiaca' => ['label' => 'Frequência cardíaca', 'type' => 'number', 'rules' => 'nullable|integer', 'col' => 'col-md-3 clinical-field'],
            'frequencia_respiratoria' => ['label' => 'Frequência respiratória', 'type' => 'number', 'rules' => 'nullable|integer', 'col' => 'col-md-3 clinical-field'],
            'peso' => ['label' => 'Peso (kg)', 'type' => 'number', 'rules' => 'nullable|numeric', 'col' => 'col-md-3 clinical-field'],
            'diagnostico' => ['label' => 'Diagnóstico', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12 clinical-field', 'rows' => 3],
            'procedimentos' => ['label' => 'Procedimentos', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12 clinical-field', 'rows' => 3],
            'medicamentos' => ['label' => 'Medicamentos', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6 clinical-field', 'rows' => 3],
            'dosagem' => ['label' => 'Dosagem', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6 clinical-field', 'rows' => 3],
            'frequencia' => ['label' => 'Frequência', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6 clinical-field', 'rows' => 3],
            'duracao_tratamento' => ['label' => 'Duração do tratamento', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6 clinical-field', 'rows' => 3],
            'recomendacoes' => ['label' => 'Recomendações', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12 clinical-field', 'rows' => 3],
            'retorno_recomendado' => ['label' => 'Retorno recomendado', 'type' => 'date', 'rules' => 'nullable|date', 'col' => 'col-md-4 clinical-field'],
        ];
    }

    private function defaults(): array
    {
        return [
            'id' => 0,
            'agendamento_id' => 0,
            'codigo' => gerar_codigo('ATD', 'atendimentos'),
            'animal_id' => '',
            'veterinario_id' => '',
            'data_atendimento' => date('Y-m-d\TH:i'),
            'queixa_principal' => '',
            'status' => 'finalizado',
            'valor' => '0',
            'gerar_prontuario' => 1,
            'anamnese' => '',
            'exame_fisico' => '',
            'temperatura' => '',
            'frequencia_cardiaca' => '',
            'frequencia_respiratoria' => '',
            'peso' => '',
            'diagnostico' => '',
            'procedimentos' => '',
            'medicamentos' => '',
            'dosagem' => '',
            'frequencia' => '',
            'duracao_tratamento' => '',
            'recomendacoes' => '',
            'retorno_recomendado' => '',
        ];
    }

    private function collect(): array
    {
        $data = [];
        foreach (['codigo', 'queixa_principal', 'valor', 'status', 'anamnese', 'exame_fisico', 'diagnostico', 'procedimentos', 'medicamentos', 'dosagem', 'frequencia', 'duracao_tratamento', 'recomendacoes', 'retorno_recomendado'] as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : $value;
            if ($data[$field] === '') {
                $data[$field] = null;
            }
        }
        foreach (['agendamento_id', 'animal_id', 'veterinario_id', 'frequencia_cardiaca', 'frequencia_respiratoria'] as $field) {
            $value = trim((string) Request::post($field, ''));
            $data[$field] = $value === '' ? null : (int) $value;
        }
        foreach (['temperatura', 'peso'] as $field) {
            $value = trim((string) Request::post($field, ''));
            $data[$field] = $value === '' ? null : $value;
        }
        $data['data_atendimento'] = $this->normalizeDateTime((string) Request::post('data_atendimento', ''));
        $data['agendamento_id'] = (int) ($data['agendamento_id'] ?? 0);
        $data['id'] = (int) Request::post('id', 0);
        return $data;
    }

    private function validate(array $data, int $id = 0): array
    {
        $errors = (new Validator([
            'codigo' => 'required|max:50|unique:atendimentos,codigo,id',
            'agendamento_id' => 'nullable|integer',
            'animal_id' => 'required|integer',
            'veterinario_id' => 'required|integer',
            'data_atendimento' => 'required|datetime',
            'queixa_principal' => 'nullable',
            'status' => 'required|in:finalizado,em_andamento,concluido,cancelado,pendente,aguardando',
            'valor' => 'required|numeric',
            'temperatura' => 'nullable|numeric',
            'frequencia_cardiaca' => 'nullable|integer',
            'frequencia_respiratoria' => 'nullable|integer',
            'peso' => 'nullable|numeric',
            'retorno_recomendado' => 'nullable|date',
        ]))->validate(array_merge(['id' => $id], $data));

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $data['data_atendimento']);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if ($parsed === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $parsed->format('Y-m-d H:i:s') !== $data['data_atendimento']) {
            $errors['data_atendimento'] = 'Informe data e hora válidas.';
        }
        if ((float) $data['valor'] < 0) {
            $errors['valor'] = 'O valor não pode ser negativo.';
        }
        if (!isset($errors['animal_id']) && Database::scalar('SELECT 1 FROM animais WHERE id = :id AND ativo = 1 LIMIT 1', ['id' => (int) $data['animal_id']]) === false) {
            $errors['animal_id'] = 'Animal não encontrado.';
        }
        if (!isset($errors['veterinario_id']) && Database::scalar('SELECT 1 FROM veterinarios WHERE id = :id AND ativo = 1 LIMIT 1', ['id' => (int) $data['veterinario_id']]) === false) {
            $errors['veterinario_id'] = 'Veterinário não encontrado.';
        }
        if ((int) $data['agendamento_id'] > 0 && Database::scalar('SELECT 1 FROM agendamentos WHERE id = :id LIMIT 1', ['id' => (int) $data['agendamento_id']]) === false) {
            $errors['agendamento_id'] = 'Agendamento não encontrado.';
        }

        return $errors;
    }

    private function clienteNomePorAnimal(int $animalId): string
    {
        $row = Database::fetch(
            'SELECT c.nome FROM animais a INNER JOIN clientes c ON c.id = a.cliente_id WHERE a.id = :id LIMIT 1',
            ['id' => $animalId]
        );
        return (string) ($row['nome'] ?? '');
    }

    private function toLocal(string $datetime): string
    {
        $value = str_replace(' ', 'T', substr($datetime, 0, 16));
        return $value !== '' ? $value : date('Y-m-d\TH:i');
    }

    private function normalizeDateTime(string $value): string
    {
        $value = str_replace('T', ' ', trim($value));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            $value .= ':00';
        }
        return $value;
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

    private function redirectTo(string $route, array $params = []): never
    {
        if ($params === []) {
            $this->redirect($route);
        }
        $target = url($route, $params);
        if (!headers_sent()) {
            header('Location: ' . $target, true, 302);
        }
        exit;
    }

    private function paginator(int $total, int $page): array
    {
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $pages);
        return [
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => self::PER_PAGE,
            'offset' => ($page - 1) * self::PER_PAGE,
        ];
    }
}
