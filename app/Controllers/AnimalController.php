<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Services\UploadService;
use PetCare\Validators\Validator;

final class AnimalController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $especieId = (int) Request::get('especie_id', 0);

        $where = ['a.ativo = 1'];
        $params = [];
        if ($search !== '') {
            $where[] = '(a.nome LIKE :q OR c.nome LIKE :q OR a.microchip LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($especieId > 0) {
            $where[] = 'a.especie_id = :especie_id';
            $params['especie_id'] = $especieId;
        }
        $sqlWhere = ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar(
            'SELECT COUNT(*) FROM animais a INNER JOIN clientes c ON c.id = a.cliente_id' . $sqlWhere,
            $params
        );
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT a.id, a.codigo, a.nome, a.foto, a.data_nascimento, a.ativo,
                    c.nome AS tutor, e.nome AS especie, r.nome AS raca
             FROM animais a
             INNER JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN especies e ON e.id = a.especie_id
             LEFT JOIN racas r ON r.id = a.raca_id' . $sqlWhere . '
             ORDER BY a.nome ASC
             LIMIT :limit OFFSET :offset',
            $params
        );

        $extraFilters = '<div class="col-md-3 col-lg-2"><label for="especie_id" class="form-label small fw-semibold">Espécie</label><select name="especie_id" id="especie_id" class="form-select"><option value="">Todas</option>';
        foreach (Database::fetchAll('SELECT id, nome FROM especies ORDER BY nome ASC') as $especie) {
            $extraFilters .= '<option value="' . e($especie['id']) . '"' . ($especieId === (int) $especie['id'] ? ' selected' : '') . '>' . e($especie['nome']) . '</option>';
        }
        $extraFilters .= '</select></div>';

        $this->render('animais/index', [
            'title' => 'Animais',
            'rows' => $rows,
            'search' => $search,
            'especie_id' => $especieId,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'actions' => [
                ['label' => 'Visualizar', 'route' => 'animais.show', 'icon' => 'eye', 'class' => 'btn-outline-primary'],
                ['label' => 'Editar', 'route' => 'animais.edit', 'icon' => 'pen', 'class' => 'btn-outline-secondary'],
                ['label' => 'Inativar', 'route' => 'animais.destroy', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Deseja inativar este animal?'],
            ],
        ]);
    }

    public function create(): void
    {
        $defaults = $this->defaults();
        $defaults['codigo'] = gerar_codigo('ANI', 'animais');
        $defaults['cliente_id'] = (int) Request::get('cliente_id', 0) ?: '';
        $defaults['castrado'] = 0;
        $defaults['ativo'] = 1;

        $this->render('animais/form', [
            'title' => 'Novo animal',
            'values' => $this->withOld($defaults),
            'errors' => [],
            'action' => 'animais.store',
            'cancelRoute' => 'animais.index',
            'submitLabel' => 'Cadastrar animal',
            'fields' => $this->fields(),
            'enctype' => 'multipart/form-data',
        ]);
    }

    public function store(): void
    {
        $data = $this->collect();
        if (($data['codigo'] ?? '') === '') {
            $data['codigo'] = gerar_codigo('ANI', 'animais');
        }
        $errors = $this->validate($data, 0);
        $photo = null;

        if ($errors === []) {
            try {
                $photo = UploadService::handle(Request::file('foto'), 'animais', 'image');
            } catch (\RuntimeException $e) {
                $errors['foto'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            $this->render('animais/form', [
                'title' => 'Novo animal',
                'values' => $data + ['foto' => ''],
                'errors' => $errors,
                'action' => 'animais.store',
                'cancelRoute' => 'animais.index',
                'submitLabel' => 'Cadastrar animal',
                'fields' => $this->fields(),
                'enctype' => 'multipart/form-data',
            ]);
            return;
        }

        if ($photo !== null) {
            $data['foto'] = $photo;
        }

        try {
            $id = Database::transaction(static fn(): int => Database::insert('animais', $data));
        } catch (\Throwable $e) {
            if ($photo !== null) {
                UploadService::delete($photo);
            }
            throw $e;
        }

        log_action('criou_animal', 'animais', $id, 'Animal ' . $data['nome'] . ' cadastrado.');
        clear_old();
        flash('success', 'Animal cadastrado com sucesso.');
        $this->redirect('animais.index');
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? (int) Request::input('id', 0);
        $record = Database::fetch(
            'SELECT a.*, c.id AS tutor_id, c.codigo AS tutor_codigo, c.nome AS tutor_nome,
                    c.telefone AS tutor_telefone, c.email AS tutor_email,
                    e.nome AS especie, r.nome AS raca
             FROM animais a
             INNER JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN especies e ON e.id = a.especie_id
             LEFT JOIN racas r ON r.id = a.raca_id
             WHERE a.id = :id
             LIMIT 1',
            ['id' => $id]
        );
        if ($record === null) {
            abort(404, 'Animal não encontrado.');
        }

        $animal = $record;
        $tutor = [
            'id' => (int) $record['tutor_id'],
            'codigo' => $record['tutor_codigo'],
            'nome' => $record['tutor_nome'],
            'telefone' => $record['tutor_telefone'],
            'email' => $record['tutor_email'],
        ];

        $prontuarios = Database::fetchAll(
            'SELECT p.id, p.data_atendimento, p.diagnostico, p.procedimentos, v.nome AS veterinario
             FROM prontuarios p
             LEFT JOIN veterinarios v ON v.id = p.veterinario_id
             WHERE p.animal_id = :id
             ORDER BY p.data_atendimento DESC
             LIMIT 20',
            ['id' => $id]
        );

        $vacinacoes = Database::fetchAll(
            'SELECT vc.id, v.nome AS vacina, vc.data_aplicacao, vc.proxima_dose, vc.lote
             FROM vacinacoes vc
             INNER JOIN vacinas v ON v.id = vc.vacina_id
             WHERE vc.animal_id = :id
             ORDER BY vc.data_aplicacao DESC
             LIMIT 20',
            ['id' => $id]
        );

        $exames = Database::fetchAll(
            'SELECT ex.id, ex.codigo, ex.tipo, ex.data_solicitacao, ex.data_resultado, ex.status, ex.laboratorio
             FROM exames ex
             WHERE ex.animal_id = :id
             ORDER BY ex.data_solicitacao DESC
             LIMIT 20',
            ['id' => $id]
        );

        $receitas = Database::fetchAll(
            'SELECT rc.id, rc.codigo, rc.data_prescricao, rc.orientacoes, v.nome AS veterinario
             FROM receitas rc
             LEFT JOIN veterinarios v ON v.id = rc.veterinario_id
             WHERE rc.animal_id = :id
             ORDER BY rc.data_prescricao DESC
             LIMIT 20',
            ['id' => $id]
        );

        $banhos = Database::fetchAll(
            'SELECT bt.id, bt.codigo, bt.data_servico, s.nome AS servico, bt.valor, bt.status
             FROM banho_tosa bt
             INNER JOIN servicos s ON s.id = bt.servico_id
             WHERE bt.animal_id = :id
             ORDER BY bt.data_servico DESC
             LIMIT 20',
            ['id' => $id]
        );

        $sections = [
            'Dados do animal' => [
                'Código' => $animal['codigo'],
                'Nome' => $animal['nome'],
                'Espécie' => $animal['especie'],
                'Raça' => $animal['raca'],
                'Sexo' => $animal['sexo'],
                'Nascimento' => date_br($animal['data_nascimento']),
                'Idade' => age($animal['data_nascimento']),
                'Cor' => $animal['cor'],
                'Peso' => $animal['peso'] !== null && $animal['peso'] !== '' ? $animal['peso'] . ' kg' : null,
                'Porte' => $animal['porte'],
                'Microchip' => $animal['microchip'],
                'Castrado' => (int) $animal['castrado'] === 1 ? 'Sim' : 'Não',
                'Situação' => static fn(): string => status_badge((int) ($animal['ativo'] ?? 0) === 1 ? 'ativo' : 'inativo'),
            ],
            'Tutor' => [
                'Nome' => $tutor['nome'],
                'Código' => $tutor['codigo'],
                'Telefone' => $tutor['telefone'],
                'E-mail' => $tutor['email'],
            ],
            'Saúde e cuidados' => [
                'Alergias' => $animal['alergias'],
                'Restrições alimentares' => $animal['restricoes_alimentares'],
                'Doenças preexistentes' => $animal['doencas_preexistentes'],
                'Observações' => $animal['observacoes'],
            ],
        ];

        $links = [
            'prontuario' => url('prontuarios.create', ['animal_id' => $id]),
            'vacina' => url('vacinas.create', ['animal_id' => $id]),
            'exame' => url('exames.create', ['animal_id' => $id]),
            'receita' => url('receitas.create', ['animal_id' => $id]),
            'estetica' => url('banho_tosa.create', ['animal_id' => $id]),
            'editar' => url('animais.edit', ['id' => $id]),
            'voltar' => url('animais.index'),
            'tutor' => url('clientes.show', ['id' => $tutor['id']]),
        ];

        $this->render('animais/show', [
            'title' => 'Ficha do animal',
            'animal' => $animal,
            'tutor' => $tutor,
            'prontuarios' => $prontuarios,
            'vacinacoes' => $vacinacoes,
            'exames' => $exames,
            'receitas' => $receitas,
            'banhos' => $banhos,
            'sections' => $sections,
            'links' => $links,
            'recordId' => $id,
        ]);
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) Request::input('id', 0);
        $animal = Database::fetch('SELECT * FROM animais WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($animal === null) {
            abort(404, 'Animal não encontrado.');
        }

        $this->render('animais/form', [
            'title' => 'Editar animal',
            'values' => $this->withOld($animal),
            'errors' => [],
            'action' => 'animais.update',
            'cancelRoute' => 'animais.show',
            'cancelParams' => ['id' => $id],
            'submitLabel' => 'Salvar alterações',
            'fields' => $this->fields(),
            'enctype' => 'multipart/form-data',
        ]);
    }

    public function update(): void
    {
        $id = (int) Request::post('id', 0);
        $animal = Database::fetch('SELECT * FROM animais WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($animal === null) {
            abort(404, 'Animal não encontrado.');
        }

        $data = $this->collect($id);
        $errors = $this->validate($data, $id);
        $photo = null;

        if ($errors === []) {
            try {
                $photo = UploadService::handle(Request::file('foto'), 'animais', 'image');
            } catch (\RuntimeException $e) {
                $errors['foto'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            $this->render('animais/form', [
                'title' => 'Editar animal',
                'values' => $data + ['id' => $id, 'foto' => $animal['foto']],
                'errors' => $errors,
                'action' => 'animais.update',
                'cancelRoute' => 'animais.show',
                'cancelParams' => ['id' => $id],
                'submitLabel' => 'Salvar alterações',
                'fields' => $this->fields(),
                'enctype' => 'multipart/form-data',
            ]);
            return;
        }

        if ($photo !== null) {
            $data['foto'] = $photo;
        }

        try {
            Database::transaction(static fn(): int => Database::update('animais', $data, 'id = :id', ['id' => $id]));
        } catch (\Throwable $e) {
            if ($photo !== null) {
                UploadService::delete($photo);
            }
            throw $e;
        }

        if ($photo !== null) {
            UploadService::delete($animal['foto']);
        }

        log_action('editou_animal', 'animais', $id, 'Dados do animal ' . $data['nome'] . ' atualizados.');
        clear_old();
        flash('success', 'Animal atualizado com sucesso.');
        $this->redirect('animais.index');
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        $animal = Database::fetch('SELECT * FROM animais WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($animal === null) {
            abort(404, 'Animal não encontrado.');
        }

        Database::transaction(static fn(): int => Database::update('animais', ['ativo' => 0], 'id = :id', ['id' => $id]));
        log_action('inativou_animal', 'animais', $id, 'Animal ' . $animal['nome'] . ' inativado.');
        flash('success', 'Animal inativado com sucesso.');
        $this->redirect('animais.index');
    }

    private function fields(): array
    {
        $tutores = [];
        foreach (Database::fetchAll('SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $tutores[(int) $row['id']] = $row['nome'];
        }
        $especies = [];
        foreach (Database::fetchAll('SELECT id, nome FROM especies ORDER BY nome ASC') as $row) {
            $especies[(int) $row['id']] = $row['nome'];
        }
        $racas = [];
        foreach (Database::fetchAll('SELECT r.id, r.nome, e.nome AS especie FROM racas r INNER JOIN especies e ON e.id = r.especie_id ORDER BY e.nome ASC, r.nome ASC') as $row) {
            $racas[(int) $row['id']] = $row['especie'] . ' / ' . $row['nome'];
        }

        return [
            'codigo' => ['label' => 'Código', 'type' => 'text', 'rules' => 'required|max:50', 'col' => 'col-md-4', 'help' => 'Gerado automaticamente.'],
            'cliente_id' => ['label' => 'Tutor', 'type' => 'select', 'rules' => 'required|integer', 'options' => $tutores, 'col' => 'col-md-8'],
            'nome' => ['label' => 'Nome do animal', 'type' => 'text', 'rules' => 'required|max:150', 'col' => 'col-md-6'],
            'foto' => ['label' => 'Foto', 'type' => 'file', 'rules' => '', 'accept' => 'image/*', 'col' => 'col-md-6'],
            'especie_id' => ['label' => 'Espécie', 'type' => 'select', 'rules' => 'required|integer', 'options' => $especies, 'col' => 'col-md-4'],
            'raca_id' => ['label' => 'Raça', 'type' => 'select', 'rules' => 'nullable|integer', 'options' => $racas, 'col' => 'col-md-4'],
            'sexo' => ['label' => 'Sexo', 'type' => 'select', 'rules' => 'nullable|in:M,F', 'options' => ['M' => 'Macho', 'F' => 'Fêmea'], 'col' => 'col-md-4'],
            'data_nascimento' => ['label' => 'Data de nascimento', 'type' => 'date', 'rules' => 'nullable|date', 'col' => 'col-md-4'],
            'cor' => ['label' => 'Cor', 'type' => 'text', 'rules' => 'nullable|max:80', 'col' => 'col-md-4'],
            'peso' => ['label' => 'Peso (kg)', 'type' => 'number', 'rules' => 'nullable|numeric', 'col' => 'col-md-4'],
            'porte' => ['label' => 'Porte', 'type' => 'select', 'rules' => 'nullable|in:Mini,Pequeno,Medio,Grande,Gigante', 'options' => ['Mini' => 'Mini', 'Pequeno' => 'Pequeno', 'Medio' => 'Médio', 'Grande' => 'Grande', 'Gigante' => 'Gigante'], 'col' => 'col-md-4'],
            'microchip' => ['label' => 'Microchip', 'type' => 'text', 'rules' => 'nullable|max:50|unique:animais,microchip,id', 'col' => 'col-md-4'],
            'castrado' => ['label' => 'Castrado', 'type' => 'checkbox', 'rules' => '', 'col' => 'col-md-4'],
            'alergias' => ['label' => 'Alergias', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6', 'rows' => 3],
            'restricoes_alimentares' => ['label' => 'Restrições alimentares', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6', 'rows' => 3],
            'doencas_preexistentes' => ['label' => 'Doenças preexistentes', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6', 'rows' => 3],
            'observacoes' => ['label' => 'Observações', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6', 'rows' => 3],
            'ativo' => ['label' => 'Ativo', 'type' => 'checkbox', 'rules' => '', 'col' => 'col-md-4'],
        ];
    }

    private function defaults(): array
    {
        return [
            'codigo' => '',
            'cliente_id' => '',
            'nome' => '',
            'foto' => '',
            'especie_id' => '',
            'raca_id' => '',
            'sexo' => '',
            'data_nascimento' => '',
            'cor' => '',
            'peso' => '',
            'porte' => '',
            'microchip' => '',
            'castrado' => 0,
            'alergias' => '',
            'restricoes_alimentares' => '',
            'doencas_preexistentes' => '',
            'observacoes' => '',
            'ativo' => 0,
        ];
    }

    private function collect(int $id = 0): array
    {
        $data = [];
        foreach (['codigo', 'nome', 'sexo', 'data_nascimento', 'cor', 'peso', 'porte', 'microchip', 'alergias', 'restricoes_alimentares', 'doencas_preexistentes', 'observacoes'] as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : $value;
        }
        foreach (['cliente_id', 'especie_id', 'raca_id'] as $field) {
            $value = trim((string) Request::post($field, ''));
            $data[$field] = $value === '' ? null : (int) $value;
        }
        foreach (['sexo', 'porte', 'data_nascimento', 'cor', 'peso', 'microchip', 'alergias', 'restricoes_alimentares', 'doencas_preexistentes', 'observacoes'] as $nullable) {
            if (($data[$nullable] ?? null) === '') {
                $data[$nullable] = null;
            }
        }
        $data['castrado'] = Request::has('castrado') ? ((int) Request::post('castrado', 0) === 1 ? 1 : 0) : 0;
        $data['ativo'] = Request::has('ativo') ? ((int) Request::post('ativo', 0) === 1 ? 1 : 0) : ($id > 0 ? 0 : 1);

        return $data;
    }

    private function validate(array $data, int $id): array
    {
        $errors = (new Validator([
            'codigo' => 'required|max:50|unique:animais,codigo,id',
            'cliente_id' => 'required|integer',
            'nome' => 'required|max:150',
            'especie_id' => 'required|integer',
            'raca_id' => 'nullable|integer',
            'sexo' => 'nullable|in:M,F',
            'data_nascimento' => 'nullable|date',
            'cor' => 'nullable|max:80',
            'peso' => 'nullable|numeric',
            'porte' => 'nullable|in:Mini,Pequeno,Medio,Grande,Gigante',
            'microchip' => 'nullable|max:50|unique:animais,microchip,id',
        ]))->validate($data + ['id' => $id]);

        if (!isset($errors['cliente_id']) && $data['cliente_id'] !== null) {
            $exists = Database::scalar('SELECT 1 FROM clientes WHERE id = :id LIMIT 1', ['id' => $data['cliente_id']]);
            if ($exists === false) {
                $errors['cliente_id'] = 'Tutor não encontrado.';
            }
        }
        if (!isset($errors['especie_id']) && $data['especie_id'] !== null) {
            $exists = Database::scalar('SELECT 1 FROM especies WHERE id = :id LIMIT 1', ['id' => $data['especie_id']]);
            if ($exists === false) {
                $errors['especie_id'] = 'Espécie não encontrada.';
            }
        }
        if (!isset($errors['raca_id']) && $data['raca_id'] !== null) {
            $exists = Database::scalar('SELECT 1 FROM racas WHERE id = :id LIMIT 1', ['id' => $data['raca_id']]);
            if ($exists === false) {
                $errors['raca_id'] = 'Raça não encontrada.';
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
