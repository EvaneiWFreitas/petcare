<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class ProfissionalController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(v.nome LIKE :q OR v.crmv LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar('SELECT COUNT(*) FROM veterinarios v' . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT v.id, v.nome, v.cpf, v.crmv, v.telefone, v.email, v.cargo, v.ativo,
                    e.nome AS especialidade,
                    CASE WHEN v.ativo = 1 THEN "ativo" ELSE "inativo" END AS status
             FROM veterinarios v
             LEFT JOIN especialidades e ON e.id = v.especialidade_id' . $sqlWhere . '
             ORDER BY v.nome ASC
             LIMIT :limit OFFSET :offset',
            $params
        );

        $this->render('veterinarios/index', [
            'title' => 'Veterinários',
            'rows' => $rows,
            'search' => $search,
            'paginator' => $paginator,
            'actions' => $this->indexActions(),
        ]);
    }

    public function create(): void
    {
        $defaults = $this->defaults();
        $defaults['ativo'] = 1;

        $this->render('veterinarios/form', [
            'title' => 'Novo veterinário',
            'values' => $this->withOld($defaults),
            'errors' => [],
            'action' => 'veterinarios.store',
            'cancelRoute' => 'veterinarios.index',
            'submitLabel' => 'Cadastrar veterinário',
            'fields' => $this->fields(),
        ]);
    }

    public function store(): void
    {
        $data = $this->collect();
        $errors = $this->validate($data, 0);

        if ($errors !== []) {
            $this->render('veterinarios/form', [
                'title' => 'Novo veterinário',
                'values' => $data,
                'errors' => $errors,
                'action' => 'veterinarios.store',
                'cancelRoute' => 'veterinarios.index',
                'submitLabel' => 'Cadastrar veterinário',
                'fields' => $this->fields(),
            ]);
            return;
        }

        $id = Database::transaction(static fn(): int => Database::insert('veterinarios', $data));
        log_action('criou_veterinario', 'veterinarios', $id, 'Veterinário ' . $data['nome'] . ' cadastrado.');
        clear_old();
        flash('success', 'Veterinário cadastrado com sucesso.');
        $this->redirect('veterinarios.index');
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) Request::input('id', 0);
        $record = $this->find($id);
        if ($record === null) {
            abort(404, 'Veterinário não encontrado.');
        }

        $this->render('veterinarios/form', [
            'title' => 'Editar veterinário',
            'values' => $this->withOld($record),
            'errors' => [],
            'action' => 'veterinarios.update',
            'cancelRoute' => 'veterinarios.index',
            'submitLabel' => 'Salvar alterações',
            'fields' => $this->fields(),
        ]);
    }

    public function update(): void
    {
        $id = (int) Request::post('id', 0);
        if ($this->find($id) === null) {
            abort(404, 'Veterinário não encontrado.');
        }

        $data = $this->collect($id);
        $errors = $this->validate($data, $id);

        if ($errors !== []) {
            $this->render('veterinarios/form', [
                'title' => 'Editar veterinário',
                'values' => $data + ['id' => $id],
                'errors' => $errors,
                'action' => 'veterinarios.update',
                'cancelRoute' => 'veterinarios.index',
                'submitLabel' => 'Salvar alterações',
                'fields' => $this->fields(),
            ]);
            return;
        }

        Database::transaction(static fn(): int => Database::update('veterinarios', $data, 'id = :id', ['id' => $id]));
        log_action('editou_veterinario', 'veterinarios', $id, 'Dados do veterinário ' . $data['nome'] . ' atualizados.');
        clear_old();
        flash('success', 'Veterinário atualizado com sucesso.');
        $this->redirect('veterinarios.index');
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        $profissional = $id > 0 ? $this->find($id) : null;
        if ($profissional === null) {
            abort(404, 'Veterinário não encontrado.');
        }

        if ((int) ($profissional['ativo'] ?? 0) === 0) {
            flash('warning', 'Este veterinário já está inativo.');
            $this->redirect('veterinarios.index');
        }

        Database::transaction(static fn(): int => Database::update('veterinarios', ['ativo' => 0], 'id = :id', ['id' => $id]));
        log_action('inativou_veterinario', 'usuarios', $id, 'Veterinário inativado.');
        clear_old();
        flash('success', 'Veterinário inativado com sucesso.');
        $this->redirect('veterinarios.index');
    }

    private function indexActions(): array
    {
        $actions = [
            ['label' => 'Editar', 'route' => 'veterinarios.edit', 'icon' => 'pen', 'class' => 'btn-outline-secondary'],
        ];
        if (can('usuarios', 'delete')) {
            $actions[] = ['label' => 'Inativar', 'route' => 'veterinarios.destroy', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Inativar este veterinário? Ele não poderá mais ser selecionado em novos registros.'];
        }
        return $actions;
    }

    private function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM veterinarios WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    private function fields(): array
    {
        $especialidades = [];
        foreach (Database::fetchAll('SELECT id, nome FROM especialidades ORDER BY nome ASC') as $row) {
            $especialidades[(int) $row['id']] = $row['nome'];
        }

        return [
            'nome' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|min:3|max:150', 'col' => 'col-md-6'],
            'cpf' => ['label' => 'CPF', 'type' => 'text', 'rules' => 'nullable|cpf|unique:veterinarios,cpf,id', 'col' => 'col-md-3'],
            'rg' => ['label' => 'RG', 'type' => 'text', 'rules' => 'nullable|max:20', 'col' => 'col-md-3'],
            'telefone' => ['label' => 'Telefone', 'type' => 'text', 'rules' => 'nullable|max:20', 'col' => 'col-md-4'],
            'email' => ['label' => 'E-mail', 'type' => 'email', 'rules' => 'nullable|email|max:255', 'col' => 'col-md-4'],
            'crmv' => ['label' => 'CRMV', 'type' => 'text', 'rules' => 'nullable|max:30|unique:veterinarios,crmv,id', 'col' => 'col-md-4'],
            'endereco' => ['label' => 'Endereço', 'type' => 'text', 'rules' => 'nullable|max:255', 'col' => 'col-md-12'],
            'especialidade_id' => ['label' => 'Especialidade', 'type' => 'select', 'rules' => 'nullable|integer', 'options' => $especialidades, 'col' => 'col-md-6'],
            'cargo' => ['label' => 'Cargo', 'type' => 'text', 'rules' => 'nullable|max:100', 'col' => 'col-md-6'],
            'data_contratacao' => ['label' => 'Data de contratação', 'type' => 'date', 'rules' => 'nullable|date', 'col' => 'col-md-6'],
            'ativo' => ['label' => 'Ativo', 'type' => 'checkbox', 'rules' => '', 'col' => 'col-md-6'],
        ];
    }

    private function defaults(): array
    {
        return [
            'nome' => '',
            'cpf' => '',
            'rg' => '',
            'telefone' => '',
            'email' => '',
            'endereco' => '',
            'especialidade_id' => '',
            'crmv' => '',
            'cargo' => '',
            'data_contratacao' => '',
            'ativo' => 0,
        ];
    }

    private function collect(int $id = 0): array
    {
        $data = [];
        foreach ($this->defaults() as $field => $default) {
            $value = Request::post($field, $default);
            $data[$field] = is_string($value) ? trim($value) : $value;
        }
        $data['especialidade_id'] = ($data['especialidade_id'] === '' || $data['especialidade_id'] === null)
            ? null
            : (int) $data['especialidade_id'];
        $data['ativo'] = Request::has('ativo') ? ((int) Request::post('ativo', 0) === 1 ? 1 : 0) : ($id > 0 ? 0 : 1);

        foreach (['cpf', 'rg', 'telefone', 'email', 'endereco', 'especialidade_id', 'crmv', 'cargo', 'data_contratacao'] as $nullable) {
            if ($data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        return $data;
    }

    private function validate(array $data, int $id): array
    {
        $errors = (new Validator([
            'nome' => 'required|min:3|max:150',
            'cpf' => 'nullable|cpf|unique:veterinarios,cpf,id',
            'email' => 'nullable|email|max:255',
            'especialidade_id' => 'nullable|integer',
            'crmv' => 'nullable|max:30|unique:veterinarios,crmv,id',
            'data_contratacao' => 'nullable|date',
        ]))->validate($data + ['id' => $id]);

        if (!isset($errors['especialidade_id']) && $data['especialidade_id'] !== null) {
            $exists = Database::scalar('SELECT 1 FROM especialidades WHERE id = :id LIMIT 1', ['id' => $data['especialidade_id']]);
            if ($exists === false) {
                $errors['especialidade_id'] = 'Especialidade não encontrada.';
            }
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
