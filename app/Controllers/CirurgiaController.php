<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class CirurgiaController extends Controller
{
    private const PER_PAGE = 15;

    private const STATUS = [
        'agendada' => 'Agendada',
        'em_andamento' => 'Em andamento',
        'realizada' => 'Realizada',
        'pos_operatorio' => 'Pós-operatório',
        'concluida' => 'Concluída',
        'cancelada' => 'Cancelada',
    ];

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(a.nome LIKE :q OR cir.tipo LIKE :q OR v.nome LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $joins = ' FROM cirurgias cir
                   INNER JOIN animais a ON a.id = cir.animal_id
                   INNER JOIN clientes c ON c.id = cir.cliente_id
                   INNER JOIN veterinarios v ON v.id = cir.veterinario_id';

        $total = (int) Database::scalar('SELECT COUNT(*)' . $joins . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT cir.id, cir.codigo, cir.tipo, cir.data_cirurgia, cir.status,
                    a.nome AS pet, c.nome AS tutor, v.nome AS veterinario' . $joins . $sqlWhere . '
             ORDER BY cir.data_cirurgia DESC, cir.id DESC
             LIMIT :limit OFFSET :offset',
            $params
        );

        $this->render('cirurgias/index', [
            'title' => 'Cirurgias',
            'rows' => $rows,
            'columns' => [
                'Código' => 'codigo',
                'Data' => static fn(array $row): string => e(date_br((string) ($row['data_cirurgia'] ?? ''))),
                'Pet' => 'pet',
                'Tipo' => 'tipo',
                'Veterinário' => 'veterinario',
                'Status' => 'status',
            ],
            'route' => 'cirurgias',
            'createRoute' => 'cirurgias.create',
            'search' => $search,
            'paginator' => $paginator,
            'actions' => $this->indexActions(),
        ]);
    }

    public function create(): void
    {
        $defaults = [
            'animal_id' => (int) Request::get('animal_id', 0) ?: '',
            'cliente_id' => (int) Request::get('cliente_id', 0) ?: '',
            'veterinario_id' => (int) Request::get('veterinario_id', 0) ?: '',
            'tipo' => '',
            'data_cirurgia' => date('Y-m-d'),
            'horario' => '',
            'avaliacao_pre_operatoria' => '',
            'exames_pre_operatorios' => '',
            'anestesia' => '',
            'procedimento_realizado' => '',
            'materiais' => '',
            'evolucao_pos_operatoria' => '',
            'recomendacoes' => '',
            'retorno' => '',
            'status' => 'agendada',
        ];

        $this->render('cirurgias/form', [
            'title' => 'Nova cirurgia',
            'values' => $this->withOld($defaults),
            'errors' => [],
            'action' => 'cirurgias.store',
            'cancelRoute' => 'cirurgias.index',
            'submitLabel' => 'Agendar cirurgia',
            'fields' => $this->fields(),
        ]);
    }

    public function store(): void
    {
        $data = $this->collect();
        $errors = $this->validate($data);

        if ($errors !== []) {
            set_old($data);
            $this->render('cirurgias/form', [
                'title' => 'Nova cirurgia',
                'values' => $data,
                'errors' => $errors,
                'action' => 'cirurgias.store',
                'cancelRoute' => 'cirurgias.index',
                'submitLabel' => 'Agendar cirurgia',
                'fields' => $this->fields(),
            ]);
            return;
        }

        $payload = [
            'codigo' => gerar_codigo('CIR', 'cirurgias'),
            'animal_id' => (int) $data['animal_id'],
            'cliente_id' => (int) $data['cliente_id'],
            'veterinario_id' => (int) $data['veterinario_id'],
            'tipo' => $data['tipo'],
            'data_cirurgia' => $data['data_cirurgia'],
            'horario' => $data['horario'] === '' ? null : $this->normalizeTime($data['horario']),
            'avaliacao_pre_operatoria' => $data['avaliacao_pre_operatoria'] === '' ? null : $data['avaliacao_pre_operatoria'],
            'exames_pre_operatorios' => $data['exames_pre_operatorios'] === '' ? null : $data['exames_pre_operatorios'],
            'anestesia' => $data['anestesia'] === '' ? null : $data['anestesia'],
            'procedimento_realizado' => $data['procedimento_realizado'] === '' ? null : $data['procedimento_realizado'],
            'materiais' => $data['materiais'] === '' ? null : $data['materiais'],
            'evolucao_pos_operatoria' => $data['evolucao_pos_operatoria'] === '' ? null : $data['evolucao_pos_operatoria'],
            'recomendacoes' => $data['recomendacoes'] === '' ? null : $data['recomendacoes'],
            'retorno' => $data['retorno'] === '' ? null : $data['retorno'],
            'status' => $data['status'],
        ];

        $id = Database::transaction(static fn(): int => Database::insert('cirurgias', $payload));

        log_action('agendou_cirurgia', 'cirurgias', $id, 'Cirurgia ' . $payload['codigo'] . ' registrada.');
        clear_old();
        flash('success', 'Cirurgia cadastrada com sucesso.');
        $this->redirectTo('cirurgias.show', ['id' => $id]);
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? (int) Request::get('id', 0);
        $cirurgia = Database::fetch(
            'SELECT cir.*, a.nome AS pet, c.nome AS tutor, v.nome AS veterinario, v.crmv
             FROM cirurgias cir
             INNER JOIN animais a ON a.id = cir.animal_id
             INNER JOIN clientes c ON c.id = cir.cliente_id
             INNER JOIN veterinarios v ON v.id = cir.veterinario_id
             WHERE cir.id = :id LIMIT 1',
            ['id' => $id]
        );
        if ($cirurgia === null) {
            abort(404, 'Cirurgia não encontrada.');
        }

        $this->render('cirurgias/show', [
            'title' => 'Detalhes da cirurgia',
            'cirurgia' => $cirurgia,
            'sections' => [
                'Identificação' => [
                    'Código' => $cirurgia['codigo'],
                    'Pet' => $cirurgia['pet'],
                    'Tutor' => $cirurgia['tutor'],
                    'Veterinário' => $cirurgia['veterinario'],
                    'CRMV' => $cirurgia['crmv'] ?: '—',
                    'Tipo' => $cirurgia['tipo'],
                    'Data' => date_br((string) $cirurgia['data_cirurgia']),
                    'Horário' => $cirurgia['horario'] ? substr((string) $cirurgia['horario'], 0, 5) : '—',
                    'Status' => static fn(): string => status_badge($cirurgia['status'] ?? null),
                ],
                'Pré-operatório' => [
                    'Avaliação pré-operatória' => $cirurgia['avaliacao_pre_operatoria'],
                    'Exames pré-operatórios' => $cirurgia['exames_pre_operatorios'],
                    'Anestesia' => $cirurgia['anestesia'],
                ],
                'Procedimento e pós-operatório' => [
                    'Procedimento realizado' => $cirurgia['procedimento_realizado'],
                    'Materiais' => $cirurgia['materiais'],
                    'Evolução pós-operatória' => $cirurgia['evolucao_pos_operatoria'],
                    'Recomendações' => $cirurgia['recomendacoes'],
                    'Retorno' => date_br($cirurgia['retorno']),
                ],
            ],
            'actions' => [
                ['label' => 'Voltar', 'route' => 'cirurgias.index', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary'],
            ],
            'recordId' => $id,
            'statusOptions' => self::STATUS,
        ]);
    }

    public function update(): void
    {
        $id = (int) Request::post('id', 0);
        if (Database::scalar('SELECT 1 FROM cirurgias WHERE id = :id LIMIT 1', ['id' => $id]) === false) {
            abort(404, 'Cirurgia não encontrada.');
        }

        $data = [
            'procedimento_realizado' => trim((string) Request::post('procedimento_realizado', '')),
            'evolucao_pos_operatoria' => trim((string) Request::post('evolucao_pos_operatoria', '')),
            'recomendacoes' => trim((string) Request::post('recomendacoes', '')),
            'retorno' => trim((string) Request::post('retorno', '')),
            'status' => trim((string) Request::post('status', 'agendada')),
        ];
        $errors = (new Validator([
            'procedimento_realizado' => 'nullable',
            'evolucao_pos_operatoria' => 'nullable',
            'recomendacoes' => 'nullable',
            'retorno' => 'nullable|date',
            'status' => 'required|in:agendada,em_andamento,realizada,pos_operatorio,concluida,cancelada',
        ]))->validate($data);

        if ($errors !== []) {
            flash('error', implode(' ', array_values($errors)));
            $this->redirectTo('cirurgias.show', ['id' => $id]);
        }

        $payload = [];
        foreach (['procedimento_realizado', 'evolucao_pos_operatoria', 'recomendacoes'] as $field) {
            $payload[$field] = $data[$field] === '' ? null : $data[$field];
        }
        $payload['retorno'] = $data['retorno'] === '' ? null : $data['retorno'];
        $payload['status'] = $data['status'];

        Database::transaction(static function () use ($id, $payload): void {
            Database::update('cirurgias', $payload, 'id = :id', ['id' => $id]);
        });

        log_action('atualizou_cirurgia', 'cirurgias', $id, 'Cirurgia #' . $id . ' atualizada.');
        flash('success', 'Cirurgia atualizada com sucesso.');
        $this->redirectTo('cirurgias.show', ['id' => $id]);
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        if ($id <= 0 || (int) Database::scalar('SELECT COUNT(*) FROM cirurgias WHERE id = :id', ['id' => $id]) === 0) {
            abort(404, 'Cirurgia não encontrada.');
        }

        Database::transaction(static fn(): int => Database::delete('cirurgias', 'id = :id', ['id' => $id]));
        log_action('excluiu_cirurgia', 'cirurgias', $id, 'Cirurgia excluída.');
        clear_old();
        flash('success', 'Cirurgia excluída com sucesso.');
        $this->redirect('cirurgias.index');
    }

    private function indexActions(): array
    {
        $actions = [
            ['label' => 'Visualizar', 'route' => 'cirurgias.show', 'icon' => 'eye', 'class' => 'btn-outline-primary'],
            ['label' => 'Editar', 'route' => 'cirurgias.show', 'icon' => 'pen', 'class' => 'btn-outline-secondary'],
        ];
        if (can('cirurgias', 'delete')) {
            $actions[] = ['label' => 'Excluir', 'route' => 'cirurgias.destroy', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Excluir esta cirurgia? Esta ação não pode ser desfeita.'];
        }
        return $actions;
    }

    private function fields(): array
    {
        $animais = [];
        foreach (Database::fetchAll(
            'SELECT a.id, a.nome, c.nome AS tutor FROM animais a
             INNER JOIN clientes c ON c.id = a.cliente_id
             ORDER BY a.nome ASC'
        ) as $row) {
            $animais[(int) $row['id']] = $row['nome'] . ' · ' . $row['tutor'];
        }
        $clientes = [];
        foreach (Database::fetchAll('SELECT id, nome FROM clientes ORDER BY nome ASC') as $row) {
            $clientes[(int) $row['id']] = $row['nome'];
        }
        $veterinarios = [];
        foreach (Database::fetchAll('SELECT id, nome FROM veterinarios WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $veterinarios[(int) $row['id']] = $row['nome'];
        }

        return [
            'animal_id' => ['label' => 'Animal', 'type' => 'select', 'rules' => 'required|integer', 'options' => $animais, 'col' => 'col-md-6'],
            'cliente_id' => ['label' => 'Tutor', 'type' => 'select', 'rules' => 'required|integer', 'options' => $clientes, 'col' => 'col-md-6'],
            'veterinario_id' => ['label' => 'Veterinário', 'type' => 'select', 'rules' => 'required|integer', 'options' => $veterinarios, 'col' => 'col-md-6'],
            'tipo' => ['label' => 'Tipo de cirurgia', 'type' => 'text', 'rules' => 'required|max:150', 'col' => 'col-md-6'],
            'data_cirurgia' => ['label' => 'Data da cirurgia', 'type' => 'date', 'rules' => 'required|date', 'col' => 'col-md-4'],
            'horario' => ['label' => 'Horário', 'type' => 'time', 'rules' => 'nullable', 'col' => 'col-md-4'],
            'status' => ['label' => 'Status', 'type' => 'select', 'rules' => 'required', 'options' => self::STATUS, 'col' => 'col-md-4'],
            'avaliacao_pre_operatoria' => ['label' => 'Avaliação pré-operatória', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'exames_pre_operatorios' => ['label' => 'Exames pré-operatórios', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'anestesia' => ['label' => 'Anestesia', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'procedimento_realizado' => ['label' => 'Procedimento realizado', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'materiais' => ['label' => 'Materiais', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'evolucao_pos_operatoria' => ['label' => 'Evolução pós-operatória', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'recomendacoes' => ['label' => 'Recomendações', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'retorno' => ['label' => 'Retorno', 'type' => 'date', 'rules' => 'nullable|date', 'col' => 'col-md-4'],
        ];
    }

    private function collect(): array
    {
        $fields = [
            'animal_id', 'cliente_id', 'veterinario_id', 'tipo', 'data_cirurgia', 'horario',
            'avaliacao_pre_operatoria', 'exames_pre_operatorios', 'anestesia', 'procedimento_realizado',
            'materiais', 'evolucao_pos_operatoria', 'recomendacoes', 'retorno', 'status',
        ];
        $data = [];
        foreach ($fields as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : '';
        }
        return $data;
    }

    private function validate(array $data): array
    {
        $errors = (new Validator([
            'animal_id' => 'required|integer',
            'cliente_id' => 'required|integer',
            'veterinario_id' => 'required|integer',
            'tipo' => 'required|max:150',
            'data_cirurgia' => 'required|date',
            'horario' => 'nullable',
            'retorno' => 'nullable|date',
            'status' => 'required|in:agendada,em_andamento,realizada,pos_operatorio,concluida,cancelada',
        ]))->validate($data);

        foreach (['animal_id' => 'animais', 'cliente_id' => 'clientes'] as $field => $table) {
            if (!isset($errors[$field]) && Database::scalar('SELECT 1 FROM ' . Database::identifier($table) . ' WHERE id = :id LIMIT 1', ['id' => (int) $data[$field]]) === false) {
                $errors[$field] = 'Registro não encontrado.';
            }
        }
        if (!isset($errors['veterinario_id']) && Database::scalar('SELECT 1 FROM veterinarios WHERE id = :id LIMIT 1', ['id' => (int) $data['veterinario_id']]) === false) {
            $errors['veterinario_id'] = 'Veterinário não encontrado.';
        }
        return $errors;
    }

    private function normalizeTime(string $value): string
    {
        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
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
