<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Services\UploadService;
use PetCare\Validators\Validator;

final class ClienteController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $situacao = (string) Request::get('situacao', 'ativo');
        if (!in_array($situacao, ['ativo', 'inativo', 'todos'], true)) {
            $situacao = 'ativo';
        }

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(c.nome LIKE :q OR c.cpf LIKE :q OR c.telefone LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($situacao !== 'todos') {
            $where[] = 'c.ativo = :situacao';
            $params['situacao'] = $situacao === 'ativo' ? 1 : 0;
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar('SELECT COUNT(*) FROM clientes c' . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT c.id, c.codigo, c.nome, c.foto, c.cpf, c.telefone, c.email,
                    CASE WHEN c.ativo = 1 THEN "ativo" ELSE "inativo" END AS status
             FROM clientes c' . $sqlWhere . '
             ORDER BY c.nome ASC
             LIMIT :limit OFFSET :offset',
            $params
        );

        $options = ['ativo' => 'Ativos', 'inativo' => 'Inativos', 'todos' => 'Todos'];
        $extraFilters = '<div class="col-md-3 col-lg-2"><label for="situacao" class="form-label small fw-semibold">Situação</label><select name="situacao" id="situacao" class="form-select">';
        foreach ($options as $value => $label) {
            $extraFilters .= '<option value="' . e($value) . '"' . ($situacao === $value ? ' selected' : '') . '>' . e($label) . '</option>';
        }
        $extraFilters .= '</select></div>';

        $this->render('clientes/index', [
            'title' => 'Clientes',
            'rows' => $rows,
            'search' => $search,
            'situacao' => $situacao,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'actions' => [
                ['label' => 'Visualizar', 'route' => 'clientes.show', 'icon' => 'eye', 'class' => 'btn-outline-primary'],
                ['label' => 'Editar', 'route' => 'clientes.edit', 'icon' => 'pen', 'class' => 'btn-outline-secondary'],
                ['label' => 'Inativar', 'route' => 'clientes.destroy', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Deseja inativar este cliente?'],
            ],
        ]);
    }

    public function create(): void
    {
        $defaults = $this->defaults();
        $defaults['codigo'] = gerar_codigo('CLI', 'clientes');
        $defaults['ativo'] = 1;
        $defaults['foto'] = '';

        $this->render('clientes/form', [
            'title' => 'Novo cliente',
            'values' => $this->withOld($defaults),
            'errors' => [],
            'action' => 'clientes.store',
            'cancelRoute' => 'clientes.index',
            'submitLabel' => 'Cadastrar cliente',
            'enctype' => 'multipart/form-data',
        ]);
    }

    public function store(): void
    {
        $data = $this->collect();
        if (($data['codigo'] ?? '') === '') {
            $data['codigo'] = gerar_codigo('CLI', 'clientes');
        }
        $errors = (new Validator($this->rules()))->validate($data + ['id' => 0]);
        $photo = null;

        if ($errors === []) {
            try {
                $photo = UploadService::handle(Request::file('foto'), 'clientes', 'image');
            } catch (\RuntimeException $e) {
                $errors['foto'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            $this->render('clientes/form', [
                'title' => 'Novo cliente',
                'values' => $data + ['foto' => ''],
                'errors' => $errors,
                'action' => 'clientes.store',
                'cancelRoute' => 'clientes.index',
                'submitLabel' => 'Cadastrar cliente',
                'enctype' => 'multipart/form-data',
            ]);
            return;
        }

        if ($photo !== null) {
            $data['foto'] = $photo;
        }

        try {
            $id = Database::transaction(static fn(): int => Database::insert('clientes', $data));
        } catch (\Throwable $e) {
            UploadService::delete($photo);
            throw $e;
        }

        log_action('criou_cliente', 'clientes', $id, 'Cliente ' . $data['nome'] . ' cadastrado.');
        clear_old();
        flash('success', 'Cliente cadastrado com sucesso.');
        $this->redirect('clientes.index');
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? (int) Request::input('id', 0);
        $cliente = $this->find($id);
        if ($cliente === null) {
            abort(404, 'Cliente não encontrado.');
        }

        $pets = Database::fetchAll(
            'SELECT a.id, a.nome, a.foto, e.nome AS especie, r.nome AS raca
             FROM animais a
             LEFT JOIN especies e ON e.id = a.especie_id
             LEFT JOIN racas r ON r.id = a.raca_id
             WHERE a.cliente_id = :id
             ORDER BY a.nome ASC',
            ['id' => $id]
        );

        $atendimentos = Database::fetchAll(
            'SELECT at.id, at.animal_id, at.data_atendimento, at.queixa_principal, at.status, a.nome AS pet
             FROM atendimentos at
             INNER JOIN animais a ON a.id = at.animal_id
             WHERE a.cliente_id = :id
             ORDER BY at.data_atendimento DESC
             LIMIT 20',
            ['id' => $id]
        );

        $contas = Database::fetchAll(
            'SELECT cr.id, cr.codigo, cr.descricao, cr.valor, cr.valor_pago, cr.vencimento, cr.status
             FROM contas_receber cr
             WHERE cr.cliente_id = :id
             ORDER BY cr.vencimento DESC
             LIMIT 20',
            ['id' => $id]
        );

        $sections = [
            'Dados cadastrais' => [
                'Código' => $cliente['codigo'],
                'Nome' => $cliente['nome'],
                'CPF' => mask_cpf($cliente['cpf']),
                'RG' => $cliente['rg'],
                'Nascimento' => date_br($cliente['data_nascimento']),
                'Sexo' => $cliente['sexo'],
                'Telefone' => $cliente['telefone'],
                'WhatsApp' => $cliente['whatsapp'],
                'E-mail' => $cliente['email'],
                'Situação' => static fn(): string => status_badge((int) ($cliente['ativo'] ?? 0) === 1 ? 'ativo' : 'inativo'),
            ],
            'Endereço' => [
                'CEP' => $cliente['cep'],
                'Endereço' => $cliente['endereco'],
                'Número' => $cliente['numero'],
                'Complemento' => $cliente['complemento'],
                'Bairro' => $cliente['bairro'],
                'Cidade' => $cliente['cidade'],
                'Estado' => $cliente['estado'],
            ],
            'Observações' => [
                'Observações' => $cliente['observacoes'],
                'Cadastrado em' => date_br($cliente['created_at'], true),
            ],
        ];

        $actions = [
            ['label' => 'Editar', 'url' => url('clientes.edit', ['id' => $id]), 'class' => 'btn-outline-primary', 'icon' => 'pen'],
            ['label' => 'Voltar', 'url' => url('clientes.index'), 'class' => 'btn-outline-secondary', 'icon' => 'arrow-left'],
        ];

        $this->render('clientes/show', [
            'title' => 'Detalhes do cliente',
            'cliente' => $cliente,
            'sections' => $sections,
            'pets' => $pets,
            'atendimentos' => $atendimentos,
            'contas' => $contas,
            'recordId' => $id,
            'actions' => $actions,
        ]);
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) Request::input('id', 0);
        $cliente = $this->find($id);
        if ($cliente === null) {
            abort(404, 'Cliente não encontrado.');
        }

        $this->render('clientes/form', [
            'title' => 'Editar cliente',
            'values' => $this->withOld($cliente),
            'errors' => [],
            'action' => 'clientes.update',
            'cancelRoute' => 'clientes.index',
            'submitLabel' => 'Salvar alterações',
            'enctype' => 'multipart/form-data',
        ]);
    }

    public function update(): void
    {
        $id = (int) Request::post('id', 0);
        $cliente = $this->find($id);
        if ($cliente === null) {
            abort(404, 'Cliente não encontrado.');
        }

        $data = $this->collect($id);
        $errors = (new Validator($this->rules()))->validate($data + ['id' => $id]);
        $photo = null;

        if ($errors === []) {
            try {
                $photo = UploadService::handle(Request::file('foto'), 'clientes', 'image');
            } catch (\RuntimeException $e) {
                $errors['foto'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            $this->render('clientes/form', [
                'title' => 'Editar cliente',
                'values' => $data + ['id' => $id, 'foto' => $cliente['foto'] ?? null],
                'errors' => $errors,
                'action' => 'clientes.update',
                'cancelRoute' => 'clientes.index',
                'submitLabel' => 'Salvar alterações',
                'enctype' => 'multipart/form-data',
            ]);
            return;
        }

        if ($photo !== null) {
            $data['foto'] = $photo;
        }

        try {
            Database::transaction(static fn(): int => Database::update('clientes', $data, 'id = :id', ['id' => $id]));
        } catch (\Throwable $e) {
            UploadService::delete($photo);
            throw $e;
        }

        if ($photo !== null) {
            UploadService::delete($cliente['foto'] ?? null);
        }

        log_action('editou_cliente', 'clientes', $id, 'Dados do cliente ' . $data['nome'] . ' atualizados.');
        clear_old();
        flash('success', 'Cliente atualizado com sucesso.');
        $this->redirect('clientes.index');
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        $cliente = $this->find($id);
        if ($cliente === null) {
            abort(404, 'Cliente não encontrado.');
        }

        Database::transaction(static fn(): int => Database::update('clientes', ['ativo' => 0], 'id = :id', ['id' => $id]));
        log_action('inativou_cliente', 'clientes', $id, 'Cliente ' . $cliente['nome'] . ' inativado.');
        flash('success', 'Cliente inativado com sucesso.');
        $this->redirect('clientes.index');
    }

    private function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM clientes WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    private function defaults(): array
    {
        return [
            'codigo' => '',
            'nome' => '',
            'cpf' => '',
            'rg' => '',
            'data_nascimento' => '',
            'sexo' => '',
            'telefone' => '',
            'whatsapp' => '',
            'email' => '',
            'cep' => '',
            'endereco' => '',
            'numero' => '',
            'complemento' => '',
            'bairro' => '',
            'cidade' => '',
            'estado' => '',
            'observacoes' => '',
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

        $data['ativo'] = Request::has('ativo') ? ((int) Request::post('ativo', 0) === 1 ? 1 : 0) : ($id > 0 ? 0 : 1);

        foreach (['cpf', 'rg', 'data_nascimento', 'sexo', 'telefone', 'whatsapp', 'email', 'cep', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'observacoes'] as $nullable) {
            if ($data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        return $data;
    }

    private function rules(): array
    {
        return [
            'codigo' => 'required|max:50|unique:clientes,codigo,id',
            'nome' => 'required|min:3|max:180',
            'cpf' => 'nullable|cpf|unique:clientes,cpf,id',
            'rg' => 'nullable|max:20',
            'data_nascimento' => 'nullable|date',
            'sexo' => 'nullable|in:M,F,Outro',
            'telefone' => 'nullable|max:20',
            'whatsapp' => 'nullable|max:20',
            'email' => 'nullable|email|max:255|unique:clientes,email,id',
            'cep' => 'nullable|max:10',
            'endereco' => 'nullable|max:255',
            'numero' => 'nullable|max:20',
            'complemento' => 'nullable|max:120',
            'bairro' => 'nullable|max:120',
            'cidade' => 'nullable|max:120',
            'estado' => 'nullable|max:2',
        ];
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
