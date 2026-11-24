<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class ReceitaController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(a.nome LIKE :q OR c.nome LIKE :q OR EXISTS (SELECT 1 FROM receita_itens ri WHERE ri.receita_id = rc.id AND (ri.medicamento LIKE :q OR ri.dosagem LIKE :q)))';
            $params['q'] = '%' . $search . '%';
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $joins = ' FROM receitas rc
                   INNER JOIN animais a ON a.id = rc.animal_id
                   INNER JOIN clientes c ON c.id = rc.cliente_id
                   LEFT JOIN veterinarios v ON v.id = rc.veterinario_id';

        $total = (int) Database::scalar('SELECT COUNT(*)' . $joins . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT rc.id, rc.codigo, rc.data_prescricao, rc.controle_especial,
                    a.nome AS pet, c.nome AS tutor, v.nome AS veterinario' . $joins . $sqlWhere . '
             ORDER BY rc.data_prescricao DESC, rc.id DESC
             LIMIT :limit OFFSET :offset',
            $params
        );

        $this->render('receitas/index', [
            'title' => 'Receitas',
            'rows' => $rows,
            'columns' => [
                'Código' => 'codigo',
                'Data' => static fn(array $row): string => e(date_br((string) ($row['data_prescricao'] ?? ''))),
                'Pet' => 'pet',
                'Tutor' => 'tutor',
                'Veterinário' => 'veterinario',
                'Controle especial' => static fn(array $row): string => (int) ($row['controle_especial'] ?? 0) === 1
                    ? '<span class="badge bg-danger rounded-pill">Sim</span>'
                    : '<span class="badge bg-secondary rounded-pill">Não</span>',
            ],
            'route' => 'receitas',
            'createRoute' => 'receitas.create',
            'search' => $search,
            'paginator' => $paginator,
            'actions' => [
                ['label' => 'Visualizar', 'route' => 'receitas.show', 'icon' => 'eye', 'class' => 'btn-outline-primary'],
                ['label' => 'Imprimir', 'route' => 'receitas.print', 'icon' => 'print', 'class' => 'btn-outline-secondary'],
            ],
        ]);
    }

    public function create(): void
    {
        $defaults = [
            'animal_id' => (int) Request::get('animal_id', 0) ?: '',
            'cliente_id' => (int) Request::get('cliente_id', 0) ?: '',
            'veterinario_id' => (int) Request::get('veterinario_id', 0) ?: '',
            'data_prescricao' => date('Y-m-d'),
            'orientacoes' => '',
            'controle_especial' => 0,
            'assinatura' => '',
            'medicamentos' => [],
        ];

        $this->render('receitas/form', [
            'title' => 'Nova receita',
            'values' => $this->withOld($defaults),
            'errors' => [],
            'action' => 'receitas.store',
            'cancelRoute' => 'receitas.index',
            'submitLabel' => 'Emitir receita',
            'options' => $this->options(),
        ]);
    }

    public function store(): void
    {
        $data = $this->collect();
        $errors = $this->validate($data);

        if ($errors !== []) {
            set_old($data);
            $this->render('receitas/form', [
                'title' => 'Nova receita',
                'values' => $data,
                'errors' => $errors,
                'action' => 'receitas.store',
                'cancelRoute' => 'receitas.index',
                'submitLabel' => 'Emitir receita',
                'options' => $this->options(),
            ]);
            return;
        }

        $payload = [
            'codigo' => gerar_codigo('REC', 'receitas'),
            'animal_id' => (int) $data['animal_id'],
            'cliente_id' => (int) $data['cliente_id'],
            'veterinario_id' => (int) $data['veterinario_id'],
            'data_prescricao' => $data['data_prescricao'],
            'orientacoes' => $data['orientacoes'] === '' ? null : $data['orientacoes'],
            'controle_especial' => (int) $data['controle_especial'],
            'assinatura' => $data['assinatura'] === '' ? null : $data['assinatura'],
        ];
        $itens = $data['medicamentos'];

        $id = Database::transaction(static function () use ($payload, $itens): int {
            $receitaId = Database::insert('receitas', $payload);
            foreach ($itens as $item) {
                Database::insert('receita_itens', [
                    'receita_id' => $receitaId,
                    'medicamento' => $item['medicamento'],
                    'dosagem' => $item['dosagem'] === '' ? null : $item['dosagem'],
                    'via_administracao' => $item['via_administracao'] === '' ? null : $item['via_administracao'],
                    'frequencia' => $item['frequencia'] === '' ? null : $item['frequencia'],
                    'duracao' => $item['duracao'] === '' ? null : $item['duracao'],
                    'observacoes' => $item['observacoes'] === '' ? null : $item['observacoes'],
                ]);
            }
            return $receitaId;
        });

        log_action('emitiu_receita', 'receitas', $id, 'Receita ' . $payload['codigo'] . ' emitida.');
        clear_old();
        flash('success', 'Receita emitida com sucesso.');
        $this->redirectTo('receitas.show', ['id' => $id]);
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? (int) Request::get('id', 0);
        $data = $this->load($id);

        $this->render('receitas/show', [
            'title' => 'Detalhes da receita',
            'receita' => $data['receita'],
            'itens' => $data['itens'],
            'sections' => [
                'Receita' => [
                    'Código' => $data['receita']['codigo'],
                    'Data da prescrição' => date_br((string) $data['receita']['data_prescricao']),
                    'Pet' => $data['receita']['pet'],
                    'Tutor' => $data['receita']['tutor'],
                    'Veterinário' => $data['receita']['veterinario'],
                    'CRMV' => $data['receita']['crmv'] ?: '—',
                    'Controle especial' => static fn(): string => (int) $data['receita']['controle_especial'] === 1
                        ? '<span class="badge bg-danger rounded-pill">Sim</span>'
                        : '<span class="badge bg-secondary rounded-pill">Não</span>',
                    'Emitida em' => date_br((string) $data['receita']['created_at'], true),
                ],
                'Orientações' => [
                    'Orientações' => $data['receita']['orientacoes'],
                    'Assinatura' => $data['receita']['assinatura'],
                ],
            ],
            'actions' => [
                ['label' => 'Imprimir', 'route' => 'receitas.print', 'icon' => 'print', 'class' => 'btn-outline-success'],
                ['label' => 'Voltar', 'route' => 'receitas.index', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary'],
            ],
            'recordId' => $id,
        ]);
    }

    public function printRecipe(): void
    {
        $id = (int) Request::get('id', 0);
        $data = $this->load($id);
        $this->render('receitas/print', [
            'title' => 'Receita ' . $data['receita']['codigo'],
            'receita' => $data['receita'],
            'itens' => $data['itens'],
        ], 'print');
    }

    private function load(int $id): array
    {
        $receita = Database::fetch(
            'SELECT rc.*, a.nome AS pet, c.nome AS tutor, c.telefone AS tutor_telefone, c.email AS tutor_email,
                    v.nome AS veterinario, v.crmv, v.cargo, e.nome AS especialidade
             FROM receitas rc
             INNER JOIN animais a ON a.id = rc.animal_id
             INNER JOIN clientes c ON c.id = rc.cliente_id
             LEFT JOIN veterinarios v ON v.id = rc.veterinario_id
             LEFT JOIN especialidades e ON e.id = v.especialidade_id
             WHERE rc.id = :id LIMIT 1',
            ['id' => $id]
        );
        if ($receita === null) {
            abort(404, 'Receita não encontrada.');
        }
        $itens = Database::fetchAll(
            'SELECT * FROM receita_itens WHERE receita_id = :id ORDER BY id ASC',
            ['id' => $id]
        );
        return ['receita' => $receita, 'itens' => $itens];
    }

    private function options(): array
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
        foreach (Database::fetchAll('SELECT id, nome, crmv FROM veterinarios WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $veterinarios[(int) $row['id']] = $row['nome'] . ($row['crmv'] ? ' · CRMV ' . $row['crmv'] : '');
        }
        return ['animais' => $animais, 'clientes' => $clientes, 'veterinarios' => $veterinarios];
    }

    private function collect(): array
    {
        $data = [];
        foreach (['orientacoes', 'assinatura'] as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : '';
        }
        foreach (['animal_id', 'cliente_id', 'veterinario_id', 'data_prescricao'] as $field) {
            $data[$field] = trim((string) Request::post($field, ''));
        }
        $data['controle_especial'] = (int) Request::post('controle_especial', 0) === 1 ? 1 : 0;
        $raw = Request::post('medicamentos', []);
        $itens = [];
        if (is_array($raw)) {
            foreach ($raw as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $item = [];
                foreach (['medicamento', 'dosagem', 'via_administracao', 'frequencia', 'duracao', 'observacoes'] as $field) {
                    $value = $row[$field] ?? '';
                    $item[$field] = is_string($value) ? trim($value) : '';
                }
                if ($item['medicamento'] !== '') {
                    $itens[] = $item;
                }
            }
        }
        $data['medicamentos'] = $itens;
        return $data;
    }

    private function validate(array $data): array
    {
        $errors = (new Validator([
            'animal_id' => 'required|integer',
            'cliente_id' => 'required|integer',
            'veterinario_id' => 'required|integer',
            'data_prescricao' => 'required|date',
            'orientacoes' => 'nullable',
            'assinatura' => 'nullable',
            'controle_especial' => 'required|in:0,1',
        ]))->validate($data);

        if ($data['medicamentos'] === []) {
            $errors['medicamentos'] = 'Informe ao menos um medicamento.';
        }
        foreach (['animal_id' => 'animais', 'cliente_id' => 'clientes'] as $field => $table) {
            if (!isset($errors[$field]) && Database::scalar('SELECT 1 FROM ' . Database::identifier($table) . ' WHERE id = :id LIMIT 1', ['id' => (int) $data[$field]]) === false) {
                $errors[$field] = 'Registro não encontrado.';
            }
        }
        $veterinario = null;
        if ($data['veterinario_id'] !== '') {
            $veterinario = Database::fetch('SELECT id, crmv FROM veterinarios WHERE id = :id LIMIT 1', ['id' => (int) $data['veterinario_id']]);
            if ($veterinario === null) {
                $errors['veterinario_id'] = 'Veterinário não encontrado.';
            }
        }
        if ((int) $data['controle_especial'] === 1 && ($veterinario === null || trim((string) ($veterinario['crmv'] ?? '')) === '')) {
            $errors['controle_especial'] = 'Medicamentos sujeitos a controle especial exigem CRMV do veterinário.';
        }
        return $errors;
    }

    private function withOld(array $defaults): array
    {
        $values = [];
        foreach ($defaults as $key => $default) {
            $values[$key] = old($key, $default);
        }
        clear_old();
        if (!is_array($values['medicamentos'] ?? null)) {
            $values['medicamentos'] = [];
        }
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
