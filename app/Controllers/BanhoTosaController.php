<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class BanhoTosaController extends Controller
{
    private const PER_PAGE = 15;

    private const STATUS = [
        'agendado' => 'Agendado',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
    ];

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(a.nome LIKE :q OR c.nome LIKE :q OR s.nome LIKE :q OR bt.codigo LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $from = ' FROM banho_tosa bt
                  INNER JOIN animais a ON a.id = bt.animal_id
                  INNER JOIN clientes c ON c.id = bt.cliente_id
                  INNER JOIN servicos s ON s.id = bt.servico_id
                  LEFT JOIN usuarios u ON u.id = bt.profissional_id';

        $total = (int) Database::scalar('SELECT COUNT(*)' . $from . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];
        $rows = Database::fetchAll(
            'SELECT bt.*, a.nome AS pet, c.nome AS tutor, s.nome AS servico, u.nome AS profissional' . $from . $sqlWhere . '
             ORDER BY bt.data_servico DESC, bt.id DESC LIMIT :limit OFFSET :offset',
            $params
        );

        $columns = [
            'Código' => 'codigo',
            'Data' => static fn(array $row): string => e(date_br((string) ($row['data_servico'] ?? ''), true)),
            'Pet' => 'pet',
            'Tutor' => 'tutor',
            'Serviço' => 'servico',
            'Profissional' => static fn(array $row): string => e($row['profissional'] ?? '—'),
            'Valor' => static fn(array $row): string => e(money($row['valor'] ?? 0)),
            'Status' => 'status',
            'Ações' => static function (array $row): string {
                $id = (int) ($row['id'] ?? 0);
                $html = '<div class="d-flex justify-content-end gap-1 flex-wrap">';
                $html .= render_action('banho_tosa.show', ['id' => $id], ['label' => 'Visualizar', 'icon' => 'eye', 'class' => 'btn-outline-primary']);
                if (can('banho_tosa', 'delete')) {
                    $html .= render_action('banho_tosa.destroy', ['id' => $id], ['label' => 'Excluir', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Excluir este serviço? Esta ação não pode ser desfeita.']);
                }
                return $html . '</div>';
            },
        ];

        $this->render('banho_tosa/index', [
            'title' => 'Banho e tosa',
            'rows' => $rows,
            'columns' => $columns,
            'route' => 'banho_tosa',
            'createRoute' => can('banho_tosa', 'create') ? 'banho_tosa.create' : null,
            'search' => $search,
            'paginator' => $paginator,
            'actions' => [],
        ]);
    }

    public function create(): void
    {
        $animalId = (int) Request::get('animal_id', 0);
        $clienteId = (int) Request::get('cliente_id', 0);
        if ($animalId > 0 && $clienteId <= 0) {
            $clienteId = (int) Database::scalar('SELECT cliente_id FROM animais WHERE id = :id LIMIT 1', ['id' => $animalId]);
        }

        $defaults = [
            'codigo' => gerar_codigo('BT', 'banho_tosa'),
            'animal_id' => $animalId > 0 ? $animalId : '',
            'cliente_id' => $clienteId > 0 ? $clienteId : '',
            'profissional_id' => '',
            'servico_id' => '',
            'data_servico' => date('Y-m-d\TH:i'),
            'observacoes' => '',
            'valor' => '0.00',
            'status' => 'agendado',
        ];
        $this->renderForm('Novo banho e tosa', $this->withOld($defaults), []);
    }

    public function store(): void
    {
        $data = $this->collect();
        if (($data['codigo'] ?? '') === '') {
            $data['codigo'] = gerar_codigo('BT', 'banho_tosa');
        }
        $errors = $this->validate($data);
        if ($errors !== []) {
            set_old($data);
            $this->renderForm('Novo banho e tosa', $data, $errors);
            return;
        }

        $payload = [
            'codigo' => $data['codigo'],
            'animal_id' => (int) $data['animal_id'],
            'cliente_id' => (int) $data['cliente_id'],
            'profissional_id' => $data['profissional_id'] === '' ? null : (int) $data['profissional_id'],
            'servico_id' => (int) $data['servico_id'],
            'data_servico' => $data['data_servico'],
            'observacoes' => $data['observacoes'] !== '' ? $data['observacoes'] : null,
            'valor' => number_format((float) $data['valor'], 2, '.', ''),
            'status' => $data['status'],
        ];

        $id = Database::transaction(function () use ($payload): int {
            $servicoId = Database::insert('banho_tosa', $payload);
            if ($payload['status'] === 'concluido' && (float) $payload['valor'] > 0) {
                Database::insert('contas_receber', [
                    'codigo' => gerar_codigo('CR', 'contas_receber'),
                    'cliente_id' => (int) $payload['cliente_id'],
                    'origem' => 'Banho e tosa',
                    'referencia_id' => $servicoId,
                    'descricao' => 'Banho e tosa ' . $payload['codigo'],
                    'valor' => $payload['valor'],
                    'valor_pago' => 0,
                    'desconto' => 0,
                    'vencimento' => substr($payload['data_servico'], 0, 10),
                    'status' => 'aberto',
                ]);
            }
            return $servicoId;
        });

        log_action('registrou_banho_tosa', 'banho_tosa', $id, 'Serviço ' . $data['codigo'] . ' registrado.');
        clear_old();
        flash('success', 'Banho e tosa registrado com sucesso.');
        $this->redirectTo('banho_tosa.show', ['id' => $id]);
    }

    public function show(): void
    {
        $id = (int) Request::input('id', 0);
        $servico = Database::fetch(
            'SELECT bt.*, a.nome AS pet, c.nome AS tutor, s.nome AS servico, u.nome AS profissional
             FROM banho_tosa bt
             INNER JOIN animais a ON a.id = bt.animal_id
             INNER JOIN clientes c ON c.id = bt.cliente_id
             INNER JOIN servicos s ON s.id = bt.servico_id
             LEFT JOIN usuarios u ON u.id = bt.profissional_id
             WHERE bt.id = :id LIMIT 1',
            ['id' => $id]
        );
        if ($servico === null) {
            abort(404, 'Serviço de banho e tosa não encontrado.');
        }

        $sections = [
            'Serviço' => [
                'Código' => $servico['codigo'],
                'Pet' => $servico['pet'],
                'Tutor' => $servico['tutor'],
                'Serviço' => $servico['servico'],
                'Profissional' => $servico['profissional'] ?: '—',
                'Data e hora' => date_br((string) $servico['data_servico'], true),
                'Valor' => static fn(): string => e(money($servico['valor'] ?? 0)),
                'Status' => static fn(): string => status_badge($servico['status'] ?? null),
            ],
            'Observações' => [
                'Detalhes' => $servico['observacoes'] ?: '—',
                'Registrado em' => date_br((string) ($servico['created_at'] ?? ''), true),
            ],
        ];

        $conta = Database::fetch(
            "SELECT id, codigo, valor, status FROM contas_receber WHERE origem = 'Banho e tosa' AND referencia_id = :id ORDER BY id DESC LIMIT 1",
            ['id' => $id]
        );

        $this->render('banho_tosa/show', [
            'title' => 'Detalhes do banho e tosa',
            'servico' => $servico,
            'conta' => $conta,
            'sections' => $sections,
            'actions' => [
                ['label' => 'Novo agendamento', 'route' => 'banho_tosa.create', 'icon' => 'plus', 'class' => 'btn-outline-primary'],
                ['label' => 'Voltar', 'route' => 'banho_tosa.index', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary'],
            ],
            'recordId' => $id,
        ]);
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        if ($id <= 0 || (int) Database::scalar('SELECT COUNT(*) FROM banho_tosa WHERE id = :id', ['id' => $id]) === 0) {
            abort(404, 'Serviço não encontrado.');
        }

        $conta = Database::fetch(
            "SELECT id, valor_pago FROM contas_receber WHERE origem = 'Banho e tosa' AND referencia_id = :id ORDER BY id DESC LIMIT 1",
            ['id' => $id]
        );
        if ($conta !== null && (float) ($conta['valor_pago'] ?? 0) > 0) {
            flash('error', 'Não é possível excluir: já existe pagamento registrado para este serviço.');
            $this->redirect('banho_tosa.index');
        }

        Database::transaction(static function () use ($id, $conta): void {
            if ($conta !== null) {
                Database::delete('contas_receber', 'id = :id', ['id' => (int) $conta['id']]);
            }
            Database::delete('banho_tosa', 'id = :id', ['id' => $id]);
        });
        log_action('excluiu_banho_tosa', 'banho_tosa', $id, 'Serviço de banho e tosa excluído.');
        clear_old();
        flash('success', 'Serviço excluído com sucesso.');
        $this->redirect('banho_tosa.index');
    }

    private function renderForm(string $title, array $values, array $errors): void
    {
        $animais = [];
        foreach (Database::fetchAll(
            'SELECT a.id, a.nome, c.nome AS tutor FROM animais a INNER JOIN clientes c ON c.id = a.cliente_id WHERE a.ativo = 1 ORDER BY a.nome ASC'
        ) as $row) {
            $animais[(int) $row['id']] = $row['nome'] . ' · ' . $row['tutor'];
        }
        $clientes = [];
        foreach (Database::fetchAll('SELECT id, nome FROM clientes ORDER BY nome ASC') as $row) {
            $clientes[(int) $row['id']] = $row['nome'];
        }
        $profissionais = [];
        foreach (Database::fetchAll('SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $profissionais[(int) $row['id']] = $row['nome'];
        }
        $servicos = [];
        $servicosPrecos = [];
        foreach (Database::fetchAll('SELECT id, nome, preco FROM servicos WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $servicos[(int) $row['id']] = $row['nome'] . ' · ' . money($row['preco']);
            $servicosPrecos[(int) $row['id']] = (float) $row['preco'];
        }

        $this->render('banho_tosa/form', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => 'banho_tosa.store',
            'cancelRoute' => 'banho_tosa.index',
            'submitLabel' => 'Registrar serviço',
            'fields' => [
                'codigo' => ['label' => 'Código', 'type' => 'text', 'rules' => 'required|max:50', 'col' => 'col-md-4', 'help' => 'Gerado automaticamente.'],
                'animal_id' => ['label' => 'Pet', 'type' => 'select', 'rules' => 'required|integer', 'options' => $animais, 'col' => 'col-md-4'],
                'cliente_id' => ['label' => 'Tutor', 'type' => 'select', 'rules' => 'required|integer', 'options' => $clientes, 'col' => 'col-md-4'],
                'servico_id' => ['label' => 'Serviço', 'type' => 'select', 'rules' => 'required|integer', 'options' => $servicos, 'col' => 'col-md-4'],
                'profissional_id' => ['label' => 'Profissional', 'type' => 'select', 'rules' => 'nullable|integer', 'options' => $profissionais, 'col' => 'col-md-4', 'empty' => 'Não informar'],
                'data_servico' => ['label' => 'Data e hora', 'type' => 'text', 'rules' => 'required', 'col' => 'col-md-4'],
                'valor' => ['label' => 'Valor (R$)', 'type' => 'number', 'rules' => 'required|numeric', 'col' => 'col-md-4'],
                'status' => ['label' => 'Status', 'type' => 'select', 'rules' => 'required', 'options' => self::STATUS, 'col' => 'col-md-4'],
                'observacoes' => ['label' => 'Observações', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            ],
            'servicosPrecos' => $servicosPrecos,
        ]);
    }

    private function collect(): array
    {
        $data = [
            'codigo' => trim((string) Request::post('codigo', '')),
            'animal_id' => trim((string) Request::post('animal_id', '')),
            'cliente_id' => trim((string) Request::post('cliente_id', '')),
            'profissional_id' => trim((string) Request::post('profissional_id', '')),
            'servico_id' => trim((string) Request::post('servico_id', '')),
            'data_servico' => $this->normalizeDateTime(trim((string) Request::post('data_servico', ''))),
            'observacoes' => trim((string) Request::post('observacoes', '')),
            'valor' => str_replace(',', '.', trim((string) Request::post('valor', '0'))),
            'status' => trim((string) Request::post('status', 'agendado')),
        ];
        return $data;
    }

    private function validate(array $data): array
    {
        $errors = (new Validator([
            'codigo' => 'required|max:50|unique:banho_tosa,codigo,id',
            'animal_id' => 'required|integer',
            'cliente_id' => 'required|integer',
            'profissional_id' => 'nullable|integer',
            'servico_id' => 'required|integer',
            'data_servico' => 'required',
            'observacoes' => 'nullable',
            'valor' => 'required|numeric',
            'status' => 'required',
        ]))->validate($data + ['id' => 0]);

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $data['data_servico']);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if ($parsed === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
            $errors['data_servico'] = 'Informe data e hora válidas.';
        }
        if (!array_key_exists($data['status'], self::STATUS)) {
            $errors['status'] = 'Status não é permitido.';
        }
        if (!isset($errors['valor']) && (float) $data['valor'] < 0) {
            $errors['valor'] = 'O valor não pode ser negativo.';
        }
        foreach (['animal_id' => 'animais', 'cliente_id' => 'clientes', 'servico_id' => 'servicos'] as $field => $table) {
            if (!isset($errors[$field]) && Database::scalar('SELECT 1 FROM ' . Database::identifier($table) . ' WHERE id = :id LIMIT 1', ['id' => (int) $data[$field]]) === false) {
                $errors[$field] = 'Registro não encontrado.';
            }
        }
        if ($data['profissional_id'] !== '' && Database::scalar('SELECT 1 FROM usuarios WHERE id = :id AND ativo = 1 LIMIT 1', ['id' => (int) $data['profissional_id']]) === false) {
            $errors['profissional_id'] = 'Profissional não encontrado.';
        }
        return $errors;
    }

    private function normalizeDateTime(string $value): string
    {
        $value = str_replace('T', ' ', $value);
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
        if (!headers_sent()) {
            header('Location: ' . url($route, $params), true, 302);
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
