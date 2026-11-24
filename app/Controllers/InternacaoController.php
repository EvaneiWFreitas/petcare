<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class InternacaoController extends Controller
{
    private const PER_PAGE = 15;

    private const STATUS = [
        'internado' => 'Internado',
        'alta' => 'Alta',
    ];

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(a.nome LIKE :q OR c.nome LIKE :q OR i.diagnostico LIKE :q OR i.box_leito LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $joins = ' FROM internacoes i
                   INNER JOIN animais a ON a.id = i.animal_id
                   INNER JOIN clientes c ON c.id = i.cliente_id
                   INNER JOIN veterinarios v ON v.id = i.veterinario_id';

        $total = (int) Database::scalar('SELECT COUNT(*)' . $joins . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT i.id, i.codigo, i.diagnostico, i.box_leito, i.data_entrada, i.data_alta, i.status,
                    a.nome AS pet, c.nome AS tutor' . $joins . $sqlWhere . '
             ORDER BY i.data_entrada DESC, i.id DESC
             LIMIT :limit OFFSET :offset',
            $params
        );

        $this->render('internacoes/index', [
            'title' => 'Internações',
            'rows' => $rows,
            'columns' => [
                'Código' => 'codigo',
                'Pet' => 'pet',
                'Tutor' => 'tutor',
                'Diagnóstico' => 'diagnostico',
                'Box' => 'box_leito',
                'Entrada' => static fn(array $row): string => e(date_br((string) ($row['data_entrada'] ?? ''), true)),
                'Alta' => static fn(array $row): string => e(date_br($row['data_alta'] ?? null, true)),
                'Status' => 'status',
            ],
            'route' => 'internacoes',
            'createRoute' => 'internacoes.create',
            'search' => $search,
            'paginator' => $paginator,
            'actions' => [
                ['label' => 'Visualizar', 'route' => 'internacoes.show', 'icon' => 'eye', 'class' => 'btn-outline-primary'],
            ],
        ]);
    }

    public function create(): void
    {
        $defaults = [
            'animal_id' => (int) Request::get('animal_id', 0) ?: '',
            'cliente_id' => (int) Request::get('cliente_id', 0) ?: '',
            'veterinario_id' => (int) Request::get('veterinario_id', 0) ?: '',
            'motivo' => '',
            'diagnostico' => '',
            'box_leito' => '',
            'data_entrada' => date('Y-m-d H:i:s'),
        ];

        $this->render('internacoes/form', [
            'title' => 'Nova internação',
            'values' => $this->withOld($defaults),
            'errors' => [],
            'action' => 'internacoes.store',
            'cancelRoute' => 'internacoes.index',
            'submitLabel' => 'Internar animal',
            'fields' => $this->fields(),
        ]);
    }

    public function store(): void
    {
        $data = $this->collect();
        $errors = $this->validate($data);

        if ($errors !== []) {
            set_old($data);
            $this->render('internacoes/form', [
                'title' => 'Nova internação',
                'values' => $data,
                'errors' => $errors,
                'action' => 'internacoes.store',
                'cancelRoute' => 'internacoes.index',
                'submitLabel' => 'Internar animal',
                'fields' => $this->fields(),
            ]);
            return;
        }

        $payload = [
            'codigo' => gerar_codigo('INT', 'internacoes'),
            'animal_id' => (int) $data['animal_id'],
            'cliente_id' => (int) $data['cliente_id'],
            'veterinario_id' => (int) $data['veterinario_id'],
            'motivo' => $data['motivo'],
            'diagnostico' => $data['diagnostico'] === '' ? null : $data['diagnostico'],
            'box_leito' => $data['box_leito'] === '' ? null : $data['box_leito'],
            'data_entrada' => $this->normalizeDateTime($data['data_entrada']),
            'data_alta' => null,
            'status' => 'internado',
        ];

        $id = Database::transaction(static fn(): int => Database::insert('internacoes', $payload));

        log_action('internou_animal', 'internacoes', $id, 'Internação ' . $payload['codigo'] . ' registrada.');
        clear_old();
        flash('success', 'Internação registrada com sucesso.');
        $this->redirectTo('internacoes.show', ['id' => $id]);
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? (int) Request::get('id', 0);
        $internacao = Database::fetch(
            'SELECT i.*, a.nome AS pet, c.nome AS tutor, v.nome AS veterinario, v.crmv
             FROM internacoes i
             INNER JOIN animais a ON a.id = i.animal_id
             INNER JOIN clientes c ON c.id = i.cliente_id
             INNER JOIN veterinarios v ON v.id = i.veterinario_id
             WHERE i.id = :id LIMIT 1',
            ['id' => $id]
        );
        if ($internacao === null) {
            abort(404, 'Internação não encontrada.');
        }

        $evolucoes = Database::fetchAll(
            'SELECT ie.*, COALESCE(u.nome, "Sistema") AS usuario
             FROM internacao_evolucoes ie
             LEFT JOIN usuarios u ON u.id = ie.usuario_id
             WHERE ie.internacao_id = :id
             ORDER BY ie.data_evolucao DESC, ie.id DESC',
            ['id' => $id]
        );

        $this->render('internacoes/show', [
            'title' => 'Detalhes da internação',
            'internacao' => $internacao,
            'evolucoes' => $evolucoes,
            'sections' => [
                'Identificação' => [
                    'Código' => $internacao['codigo'],
                    'Pet' => $internacao['pet'],
                    'Tutor' => $internacao['tutor'],
                    'Veterinário' => $internacao['veterinario'],
                    'CRMV' => $internacao['crmv'] ?: '—',
                    'Status' => static fn(): string => status_badge($internacao['status'] ?? null),
                ],
                'Internação' => [
                    'Motivo' => $internacao['motivo'],
                    'Diagnóstico' => $internacao['diagnostico'],
                    'Box / leito' => $internacao['box_leito'],
                    'Entrada' => date_br((string) $internacao['data_entrada'], true),
                    'Alta' => date_br($internacao['data_alta'], true),
                ],
            ],
            'actions' => [
                ['label' => 'Voltar', 'route' => 'internacoes.index', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary'],
            ],
            'recordId' => $id,
        ]);
    }

    public function evolve(): void
    {
        $id = (int) Request::post('id', 0);
        $internacao = Database::fetch('SELECT id, status FROM internacoes WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($internacao === null) {
            abort(404, 'Internação não encontrada.');
        }
        if ($internacao['status'] === 'alta') {
            flash('error', 'Não é possível registrar evoluções após a alta.');
            $this->redirectTo('internacoes.show', ['id' => $id]);
        }

        $data = [
            'data_evolucao' => trim((string) Request::post('data_evolucao', '')),
            'alimentacao' => trim((string) Request::post('alimentacao', '')),
            'hidratacao' => trim((string) Request::post('hidratacao', '')),
            'sinais_vitais' => trim((string) Request::post('sinais_vitais', '')),
            'medicamentos' => trim((string) Request::post('medicamentos', '')),
            'intercorrencias' => trim((string) Request::post('intercorrencias', '')),
        ];
        if ($data['data_evolucao'] === '') {
            $data['data_evolucao'] = date('Y-m-d H:i:s');
        }
        $errors = (new Validator([
            'data_evolucao' => 'required',
            'alimentacao' => 'nullable',
            'hidratacao' => 'nullable',
            'sinais_vitais' => 'nullable',
            'medicamentos' => 'nullable',
            'intercorrencias' => 'nullable',
        ]))->validate($data);

        $dataEvolucaoNormalizada = $this->normalizeDateTime($data['data_evolucao']);
        if ($dataEvolucaoNormalizada === '') {
            $errors['data_evolucao'] = 'Informe data e hora válidas.';
        }
        if ($errors !== []) {
            flash('error', implode(' ', array_values($errors)));
            $this->redirectTo('internacoes.show', ['id' => $id]);
        }

        $payload = [
            'internacao_id' => $id,
            'usuario_id' => (int) (user()['id'] ?? 0),
            'data_evolucao' => $dataEvolucaoNormalizada,
            'alimentacao' => $data['alimentacao'] === '' ? null : $data['alimentacao'],
            'hidratacao' => $data['hidratacao'] === '' ? null : $data['hidratacao'],
            'sinais_vitais' => $data['sinais_vitais'] === '' ? null : $data['sinais_vitais'],
            'medicamentos' => $data['medicamentos'] === '' ? null : $data['medicamentos'],
            'intercorrencias' => $data['intercorrencias'] === '' ? null : $data['intercorrencias'],
        ];

        Database::transaction(static function () use ($payload): void {
            Database::insert('internacao_evolucoes', $payload);
        });

        log_action('registrou_evolucao', 'internacoes', $id, 'Evolução registrada na internação #' . $id . '.');
        flash('success', 'Evolução registrada com sucesso.');
        $this->redirectTo('internacoes.show', ['id' => $id]);
    }

    public function discharge(): void
    {
        $id = (int) Request::post('id', 0);
        $internacao = Database::fetch('SELECT id, status, data_alta FROM internacoes WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($internacao === null) {
            abort(404, 'Internação não encontrada.');
        }
        if ($internacao['status'] === 'alta' || $internacao['data_alta'] !== null) {
            flash('error', 'Esta internação já recebeu alta.');
            $this->redirectTo('internacoes.show', ['id' => $id]);
        }

        $dataAlta = trim((string) Request::post('data_alta', ''));
        if ($dataAlta === '') {
            $dataAlta = date('Y-m-d H:i:s');
        }
        $dataAltaNormalizada = $this->normalizeDateTime($dataAlta);
        $errors = [];
        if ($dataAltaNormalizada === '') {
            $errors['data_alta'] = 'Informe data e hora válidas.';
        }
        if ($errors !== []) {
            flash('error', implode(' ', array_values($errors)));
            $this->redirectTo('internacoes.show', ['id' => $id]);
        }

        Database::transaction(static function () use ($id, $dataAltaNormalizada): void {
            Database::update('internacoes', [
                'status' => 'alta',
                'data_alta' => $dataAltaNormalizada,
            ], 'id = :id', ['id' => $id]);
        });

        log_action('deu_alta', 'internacoes', $id, 'Alta registrada na internação #' . $id . '.');
        flash('success', 'Alta registrada com sucesso.');
        $this->redirectTo('internacoes.show', ['id' => $id]);
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
            'box_leito' => ['label' => 'Box / leito', 'type' => 'text', 'rules' => 'nullable|max:60', 'col' => 'col-md-6'],
            'data_entrada' => ['label' => 'Data e hora da entrada', 'type' => 'text', 'rules' => 'required', 'col' => 'col-md-6'],
            'motivo' => ['label' => 'Motivo', 'type' => 'textarea', 'rules' => 'required', 'col' => 'col-md-12', 'rows' => 3],
            'diagnostico' => ['label' => 'Diagnóstico', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
        ];
    }

    private function collect(): array
    {
        $data = [];
        foreach (['animal_id', 'cliente_id', 'veterinario_id', 'box_leito', 'data_entrada', 'motivo', 'diagnostico'] as $field) {
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
            'box_leito' => 'nullable|max:60',
            'data_entrada' => 'required',
            'motivo' => 'required',
            'diagnostico' => 'nullable',
        ]))->validate($data);

        foreach (['animal_id' => 'animais', 'cliente_id' => 'clientes'] as $field => $table) {
            if (!isset($errors[$field]) && Database::scalar('SELECT 1 FROM ' . Database::identifier($table) . ' WHERE id = :id LIMIT 1', ['id' => (int) $data[$field]]) === false) {
                $errors[$field] = 'Registro não encontrado.';
            }
        }
        if (!isset($errors['veterinario_id']) && Database::scalar('SELECT 1 FROM veterinarios WHERE id = :id LIMIT 1', ['id' => (int) $data['veterinario_id']]) === false) {
            $errors['veterinario_id'] = 'Veterinário não encontrado.';
        }
        if ($this->normalizeDateTime($data['data_entrada']) === '') {
            $errors['data_entrada'] = 'Informe data e hora válidas.';
        }
        return $errors;
    }

    private function normalizeDateTime(string $value): string
    {
        $value = str_replace('T', ' ', trim($value));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }
        return '';
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
