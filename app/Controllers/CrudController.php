<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class CrudController extends Controller
{
    private const PER_PAGE = 15;

    private const DEPENDENCIES = [
        'especies' => [['animais', 'especie_id']],
        'racas' => [['animais', 'raca_id']],
        'especialidades' => [['veterinarios', 'especialidade_id']],
        'vacinas' => [['vacinacoes', 'vacina_id']],
        'categorias' => [['produtos', 'categoria_id']],
        'fornecedores' => [['produtos', 'fornecedor_id'], ['compras', 'fornecedor_id']],
        'servicos' => [['banho_tosa', 'servico_id']],
    ];

    public function index(): void
    {
        $slug = $this->slug();
        $config = $this->config($slug);
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));

        $where = [];
        $params = [];
        $searchFields = $config['search'] ?? [];
        if ($search !== '' && is_array($searchFields) && $searchFields !== []) {
            $parts = [];
            foreach (array_values($searchFields) as $index => $field) {
                $param = 'q' . $index;
                $parts[] = Database::identifier((string) $field) . ' LIKE :' . $param;
                $params[$param] = '%' . $search . '%';
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $table = Database::identifier((string) $config['table']);

        $total = (int) Database::scalar('SELECT COUNT(*) FROM ' . $table . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $order = (string) ($config['order'] ?? 'id ASC');
        $rows = Database::fetchAll(
            'SELECT * FROM ' . $table . $sqlWhere . ' ORDER BY ' . $order . ' LIMIT :limit OFFSET :offset',
            $params
        );

        $columns = [];
        $fields = $config['fields'] ?? [];
        foreach ($config['columns'] ?? [] as $column) {
            $column = (string) $column;
            $label = $fields[$column]['label'] ?? ($column === 'id' ? 'ID' : ucfirst(str_replace('_', ' ', $column)));
            if ($column === 'ativo') {
                $columns[$label] = static fn(array $row): string => status_badge((int) ($row['ativo'] ?? 0) === 1 ? 'ativo' : 'inativo');
            } elseif ($column === 'preco') {
                $columns[$label] = static fn(array $row): string => money($row['preco'] ?? 0);
            } else {
                $columns[$label] = $column;
            }
        }

        $route = (string) $config['route'];
        $this->render('crud/index', [
            'title' => (string) ($config['plural'] ?? $config['label'] ?? 'Registros'),
            'config' => $config,
            'slug' => $slug,
            'columns' => $columns,
            'rows' => $rows,
            'route' => $route,
            'createRoute' => $route . '.create',
            'search' => $search,
            'paginator' => $paginator,
            'actions' => [
                ['label' => 'Editar', 'route' => $route . '.edit', 'icon' => 'pen', 'class' => 'btn-outline-secondary'],
                ['label' => 'Excluir', 'route' => $route . '.destroy', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Deseja realmente excluir este registro?'],
            ],
        ]);
    }

    public function create(): void
    {
        $slug = $this->slug();
        $config = $this->config($slug);
        $fields = $config['fields'] ?? [];
        $values = [];
        foreach ($fields as $name => $field) {
            $values[$name] = ($field['type'] ?? 'text') === 'checkbox' ? 1 : '';
        }

        $this->render('crud/form', [
            'title' => 'Novo ' . mb_strtolower((string) ($config['label'] ?? 'registro')),
            'config' => $config,
            'slug' => $slug,
            'fields' => $fields,
            'values' => $this->withOld($values),
            'errors' => [],
            'action' => $config['route'] . '.store',
            'cancelRoute' => $config['route'] . '.index',
            'submitLabel' => 'Cadastrar',
        ]);
    }

    public function store(): void
    {
        $slug = $this->slug();
        $config = $this->config($slug);
        $fields = $config['fields'] ?? [];
        $data = $this->collect($fields, true);
        $errors = (new Validator($this->rules($fields)))->validate($data + ['id' => 0]);

        if ($errors !== []) {
            $this->render('crud/form', [
                'title' => 'Novo ' . mb_strtolower((string) ($config['label'] ?? 'registro')),
                'config' => $config,
                'slug' => $slug,
                'fields' => $fields,
                'values' => $data,
                'errors' => $errors,
                'action' => $config['route'] . '.store',
                'cancelRoute' => $config['route'] . '.index',
                'submitLabel' => 'Cadastrar',
            ]);
            return;
        }

        $id = Database::transaction(static fn(): int => Database::insert((string) $config['table'], $data));
        log_action('criou_' . $slug, $slug, $id, 'Registro criado em ' . $config['table'] . '.');
        clear_old();
        flash('success', ($config['label'] ?? 'Registro') . ' cadastrado com sucesso.');
        $this->redirect($config['route'] . '.index');
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) Request::input('id', 0);
        $slug = $this->slug();
        $config = $this->config($slug);
        $record = $this->find((string) $config['table'], $id);
        if ($record === null) {
            abort(404, 'Registro não encontrado.');
        }

        $fields = $config['fields'] ?? [];
        $formFields = ['id' => ['label' => 'ID', 'type' => 'text', 'rules' => '', 'col' => 'd-none']] + $fields;
        $values = $this->withOld($record);

        $this->render('crud/form', [
            'title' => 'Editar ' . mb_strtolower((string) ($config['label'] ?? 'registro')),
            'config' => $config,
            'slug' => $slug,
            'fields' => $formFields,
            'values' => $values,
            'errors' => [],
            'action' => $config['route'] . '.update',
            'cancelRoute' => $config['route'] . '.index',
            'submitLabel' => 'Salvar alterações',
        ]);
    }

    public function update(): void
    {
        $id = (int) Request::post('id', 0);
        $slug = $this->slug();
        $config = $this->config($slug);
        if ($this->find((string) $config['table'], $id) === null) {
            abort(404, 'Registro não encontrado.');
        }

        $fields = $config['fields'] ?? [];
        $data = $this->collect($fields, false);
        $errors = (new Validator($this->rules($fields)))->validate($data + ['id' => $id]);

        if ($errors !== []) {
            $formFields = ['id' => ['label' => 'ID', 'type' => 'text', 'rules' => '', 'col' => 'd-none']] + $fields;
            $this->render('crud/form', [
                'title' => 'Editar ' . mb_strtolower((string) ($config['label'] ?? 'registro')),
                'config' => $config,
                'slug' => $slug,
                'fields' => $formFields,
                'values' => $data + ['id' => $id],
                'errors' => $errors,
                'action' => $config['route'] . '.update',
                'cancelRoute' => $config['route'] . '.index',
                'submitLabel' => 'Salvar alterações',
            ]);
            return;
        }

        Database::transaction(static fn(): int => Database::update((string) $config['table'], $data, 'id = :id', ['id' => $id]));
        log_action('editou_' . $slug, $slug, $id, 'Registro atualizado em ' . $config['table'] . '.');
        clear_old();
        flash('success', ($config['label'] ?? 'Registro') . ' atualizado com sucesso.');
        $this->redirect($config['route'] . '.index');
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        $slug = $this->slug();
        $config = $this->config($slug);
        $table = (string) $config['table'];
        if ($this->find($table, $id) === null) {
            abort(404, 'Registro não encontrado.');
        }

        $inUse = 0;
        $canDeactivate = in_array($table, ['fornecedores', 'servicos'], true);

        Database::transaction(function () use ($table, $id, $canDeactivate, &$inUse): void {
            foreach (self::DEPENDENCIES[$table] ?? [] as [$relatedTable, $relatedColumn]) {
                $inUse += (int) Database::scalar(
                    'SELECT COUNT(*) FROM ' . Database::identifier($relatedTable) . ' WHERE ' . Database::identifier($relatedColumn) . ' = :id',
                    ['id' => $id]
                );
            }

            if ($inUse > 0) {
                if ($canDeactivate) {
                    Database::update($table, ['ativo' => 0], 'id = :id', ['id' => $id]);
                }
                return;
            }

            Database::delete($table, 'id = :id', ['id' => $id]);
        });

        if ($inUse > 0 && !$canDeactivate) {
            flash('error', 'Não é possível excluir: registro em uso.');
            $this->redirect($config['route'] . '.index');
        }

        if ($inUse > 0) {
            log_action('inativou_' . $slug, $slug, $id, 'Registro inativado por estar em uso.');
            flash('warning', 'Registro em uso. O item foi inativado.');
        } else {
            log_action('excluiu_' . $slug, $slug, $id, 'Registro excluído de ' . $table . '.');
            flash('success', ($config['label'] ?? 'Registro') . ' excluído com sucesso.');
        }

        $this->redirect($config['route'] . '.index');
    }

    private function slug(): string
    {
        if (preg_match('/^crud\.([a-zA-Z0-9_-]+)\./', Request::route(), $matches) !== 1) {
            abort(404, 'Módulo não encontrado.');
        }
        return $matches[1];
    }

    private function config(string $slug): array
    {
        $modules = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules.php';
        if (!isset($modules[$slug]) || !is_array($modules[$slug])) {
            abort(404, 'Módulo não encontrado.');
        }
        return $modules[$slug];
    }

    private function find(string $table, int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::identifier($table) . ' WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    private function rules(array $fields): array
    {
        $rules = [];
        foreach ($fields as $name => $field) {
            $rules[$name] = $field['rules'] ?? '';
        }
        return $rules;
    }

    private function collect(array $fields, bool $creating): array
    {
        $data = [];
        foreach ($fields as $name => $field) {
            if (($field['type'] ?? 'text') === 'checkbox') {
                $data[$name] = Request::has($name) ? ((int) Request::post($name, 0) === 1 ? 1 : 0) : ($creating ? 1 : 0);
                continue;
            }

            $value = Request::post($name, '');
            $value = is_string($value) ? trim($value) : $value;
            $rules = (string) ($field['rules'] ?? '');
            if ($value === '' && !str_contains($rules, 'required')) {
                $value = null;
            }
            $data[$name] = $value;
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
