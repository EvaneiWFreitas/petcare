<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Services\UploadService;
use PetCare\Validators\Validator;

final class ExameController extends Controller
{
    private const PER_PAGE = 15;

    private const TIPOS = [
        'Hemograma' => 'Hemograma',
        'Bioquímico' => 'Bioquímico',
        'Urina' => 'Urina',
        'Fezes' => 'Fezes',
        'Ultrassonografia' => 'Ultrassonografia',
        'Radiografia' => 'Radiografia',
        'Eletrocardiograma' => 'Eletrocardiograma',
        'Outros' => 'Outros',
    ];

    private const STATUS = [
        'solicitado' => 'Solicitado',
        'liberado' => 'Liberado',
    ];

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $status = (string) Request::get('status', '');
        if (!array_key_exists($status, self::STATUS)) {
            $status = '';
        }
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(a.nome LIKE :q OR c.nome LIKE :q OR ex.tipo LIKE :q OR ex.laboratorio LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($status !== '') {
            $where[] = 'ex.status = :status';
            $params['status'] = $status;
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $joins = ' FROM exames ex
                   INNER JOIN animais a ON a.id = ex.animal_id
                   INNER JOIN clientes c ON c.id = ex.cliente_id
                   LEFT JOIN veterinarios v ON v.id = ex.veterinario_id';

        $total = (int) Database::scalar('SELECT COUNT(*)' . $joins . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT ex.id, ex.codigo, ex.tipo, ex.laboratorio, ex.data_solicitacao, ex.status,
                    a.nome AS pet, c.nome AS tutor' . $joins . $sqlWhere . '
             ORDER BY ex.data_solicitacao DESC, ex.id DESC
             LIMIT :limit OFFSET :offset',
            $params
        );

        $extraFilters = '<div class="col-md-3 col-lg-2"><label for="status" class="form-label small fw-semibold">Status</label><select name="status" id="status" class="form-select"><option value="">Todos</option>';
        foreach (self::STATUS as $value => $label) {
            $extraFilters .= '<option value="' . e($value) . '"' . selected($status, (string) $value) . '>' . e($label) . '</option>';
        }
        $extraFilters .= '</select></div>';

        $this->render('exames/index', [
            'title' => 'Exames',
            'rows' => $rows,
            'columns' => [
                'Código' => 'codigo',
                'Data' => static fn(array $row): string => e(date_br((string) ($row['data_solicitacao'] ?? ''))),
                'Pet' => 'pet',
                'Tipo' => 'tipo',
                'Laboratório' => 'laboratorio',
                'Status' => 'status',
            ],
            'route' => 'exames',
            'createRoute' => 'exames.create',
            'search' => $search,
            'status' => $status,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'actions' => [
                ['label' => 'Visualizar', 'route' => 'exames.show', 'icon' => 'eye', 'class' => 'btn-outline-primary'],
                ['label' => 'Resultado', 'route' => 'exames.show', 'icon' => 'file-circle-check', 'class' => 'btn-outline-success'],
            ],
        ]);
    }

    public function create(): void
    {
        $defaults = [
            'animal_id' => (int) Request::get('animal_id', 0) ?: '',
            'cliente_id' => (int) Request::get('cliente_id', 0) ?: '',
            'veterinario_id' => (int) Request::get('veterinario_id', 0) ?: '',
            'prontuario_id' => '',
            'tipo' => '',
            'laboratorio' => '',
            'data_solicitacao' => date('Y-m-d'),
            'observacoes' => '',
        ];

        $this->renderForm('Solicitar exame', $this->withOld($defaults), []);
    }

    public function store(): void
    {
        $data = $this->collect();
        $errors = $this->validate($data);
        $anexo = null;
        $file = Request::file('anexo');

        if ($errors === []) {
            try {
                $anexo = UploadService::handle($file, 'exames', 'document');
            } catch (\RuntimeException $e) {
                $errors['anexo'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            set_old($data);
            $this->renderForm('Solicitar exame', $data, $errors);
            return;
        }

        $payload = [
            'codigo' => gerar_codigo('EXM', 'exames'),
            'animal_id' => (int) $data['animal_id'],
            'cliente_id' => (int) $data['cliente_id'],
            'veterinario_id' => $data['veterinario_id'] === '' ? null : (int) $data['veterinario_id'],
            'prontuario_id' => $data['prontuario_id'] === '' ? null : (int) $data['prontuario_id'],
            'tipo' => $data['tipo'],
            'laboratorio' => $data['laboratorio'] === '' ? null : $data['laboratorio'],
            'data_solicitacao' => $data['data_solicitacao'],
            'data_resultado' => null,
            'resultados' => null,
            'observacoes' => $data['observacoes'] === '' ? null : $data['observacoes'],
            'status' => 'solicitado',
        ];

        try {
            $id = Database::transaction(static function () use ($payload, $file, $anexo): int {
                $exameId = Database::insert('exames', $payload);
                if ($anexo !== null) {
                    Database::insert('anexos', [
                        'entidade' => 'exame',
                        'entidade_id' => $exameId,
                        'nome_original' => (string) ($file['name'] ?? ''),
                        'caminho' => $anexo,
                        'mime' => (string) ($file['type'] ?? ''),
                        'tamanho' => (int) ($file['size'] ?? 0),
                        'usuario_id' => (int) (user()['id'] ?? 0) ?: null,
                    ]);
                }
                return $exameId;
            });
        } catch (\Throwable $e) {
            if ($anexo !== null) {
                UploadService::delete($anexo);
            }
            throw $e;
        }

        log_action('solicitou_exame', 'exames', $id, 'Exame ' . $payload['codigo'] . ' solicitado.');
        clear_old();
        flash('success', 'Exame solicitado com sucesso.');
        $this->redirectTo('exames.show', ['id' => $id]);
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? (int) Request::get('id', 0);
        $data = $this->load($id);
        $exame = $data['exame'];

        $this->render('exames/show', [
            'title' => 'Detalhes do exame',
            'exame' => $exame,
            'resultados' => $data['resultados'],
            'anexos' => $data['anexos'],
            'statusOptions' => self::STATUS,
            'sections' => [
                'Exame' => [
                    'Código' => $exame['codigo'],
                    'Tipo' => $exame['tipo'],
                    'Laboratório' => $exame['laboratorio'],
                    'Data da solicitação' => date_br((string) $exame['data_solicitacao']),
                    'Data do resultado' => date_br($exame['data_resultado']),
                    'Status' => static fn(): string => status_badge($exame['status'] ?? null),
                    'Pet' => $exame['pet'],
                    'Tutor' => $exame['tutor'],
                    'Veterinário' => $exame['veterinario'],
                    'Prontuário' => (int) ($exame['prontuario_id'] ?? 0) > 0 ? '#' . $exame['prontuario_id'] : '—',
                    'Observações' => $exame['observacoes'],
                ],
            ],
            'actions' => [
                ['label' => 'Voltar', 'route' => 'exames.index', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary'],
            ],
            'recordId' => $id,
        ]);
    }

    public function saveResult(): void
    {
        $id = (int) Request::post('id', 0);
        $this->load($id);
        $data = [
            'resultados' => trim((string) Request::post('resultados', '')),
            'data_resultado' => trim((string) Request::post('data_resultado', '')),
            'status' => trim((string) Request::post('status', 'solicitado')),
        ];
        $errors = (new Validator([
            'resultados' => 'nullable',
            'data_resultado' => 'nullable|date',
            'status' => 'required|in:solicitado,liberado',
        ]))->validate($data);

        $raw = Request::post('resultado_descricao', []);
        $itens = [];
        if (is_array($raw)) {
            foreach ($raw as $index => $descricao) {
                $descricao = is_string($descricao) ? trim($descricao) : '';
                if ($descricao === '') {
                    continue;
                }
                $itens[] = [
                    'descricao' => $descricao,
                    'valor' => $this->arrayValue('resultado_valor', (int) $index),
                    'unidade' => $this->arrayValue('resultado_unidade', (int) $index),
                    'referencia' => $this->arrayValue('resultado_referencia', (int) $index),
                ];
            }
        }

        $file = Request::file('anexo');
        $anexo = null;
        if ($errors === []) {
            try {
                $anexo = UploadService::handle($file, 'exames', 'document');
            } catch (\RuntimeException $e) {
                $errors['anexo'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            flash('error', implode(' ', array_values($errors)));
            $this->redirectTo('exames.show', ['id' => $id]);
        }

        $payload = [
            'resultados' => $data['resultados'] === '' ? null : $data['resultados'],
            'data_resultado' => $data['data_resultado'] === '' ? null : $data['data_resultado'],
            'status' => $data['status'],
        ];

        try {
            Database::transaction(static function () use ($id, $payload, $itens, $file, $anexo): void {
                Database::update('exames', $payload, 'id = :id', ['id' => $id]);
                Database::delete('exame_resultados', 'exame_id = :id', ['id' => $id]);
                foreach ($itens as $item) {
                    Database::insert('exame_resultados', [
                        'exame_id' => $id,
                        'descricao' => $item['descricao'],
                        'valor' => $item['valor'] === '' ? null : $item['valor'],
                        'unidade' => $item['unidade'] === '' ? null : $item['unidade'],
                        'referencia' => $item['referencia'] === '' ? null : $item['referencia'],
                    ]);
                }
                if ($anexo !== null) {
                    Database::insert('anexos', [
                        'entidade' => 'exame',
                        'entidade_id' => $id,
                        'nome_original' => (string) ($file['name'] ?? ''),
                        'caminho' => $anexo,
                        'mime' => (string) ($file['type'] ?? ''),
                        'tamanho' => (int) ($file['size'] ?? 0),
                        'usuario_id' => (int) (user()['id'] ?? 0) ?: null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            if ($anexo !== null) {
                UploadService::delete($anexo);
            }
            throw $e;
        }

        log_action('registrou_resultado_exame', 'exames', $id, 'Resultado do exame registrado.');
        flash('success', 'Resultado registrado com sucesso.');
        $this->redirectTo('exames.show', ['id' => $id]);
    }

    private function arrayValue(string $key, int $index): string
    {
        $values = Request::post($key, []);
        if (!is_array($values)) {
            return '';
        }
        $value = $values[$index] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    private function load(int $id): array
    {
        $exame = Database::fetch(
            'SELECT ex.*, a.nome AS pet, c.nome AS tutor, v.nome AS veterinario
             FROM exames ex
             INNER JOIN animais a ON a.id = ex.animal_id
             INNER JOIN clientes c ON c.id = ex.cliente_id
             LEFT JOIN veterinarios v ON v.id = ex.veterinario_id
             WHERE ex.id = :id LIMIT 1',
            ['id' => $id]
        );
        if ($exame === null) {
            abort(404, 'Exame não encontrado.');
        }
        return [
            'exame' => $exame,
            'resultados' => Database::fetchAll('SELECT * FROM exame_resultados WHERE exame_id = :id ORDER BY id ASC', ['id' => $id]),
            'anexos' => Database::fetchAll("SELECT * FROM anexos WHERE entidade = 'exame' AND entidade_id = :id ORDER BY created_at DESC, id DESC", ['id' => $id]),
        ];
    }

    private function renderForm(string $title, array $values, array $errors): void
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

        $this->render('exames/form', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => 'exames.store',
            'cancelRoute' => 'exames.index',
            'submitLabel' => 'Solicitar exame',
            'enctype' => 'multipart/form-data',
            'fields' => [
                'animal_id' => ['label' => 'Animal', 'type' => 'select', 'rules' => 'required|integer', 'options' => $animais, 'col' => 'col-md-6'],
                'cliente_id' => ['label' => 'Tutor', 'type' => 'select', 'rules' => 'required|integer', 'options' => $clientes, 'col' => 'col-md-6'],
                'veterinario_id' => ['label' => 'Veterinário', 'type' => 'select', 'rules' => 'nullable|integer', 'options' => $veterinarios, 'col' => 'col-md-4', 'empty' => 'Não informar'],
                'prontuario_id' => ['label' => 'Prontuário', 'type' => 'number', 'rules' => 'nullable|integer', 'col' => 'col-md-4', 'help' => 'Opcional. Informe o ID do prontuário, se houver.'],
                'tipo' => ['label' => 'Tipo de exame', 'type' => 'select', 'rules' => 'required', 'options' => self::TIPOS, 'col' => 'col-md-4'],
                'laboratorio' => ['label' => 'Laboratório', 'type' => 'text', 'rules' => 'nullable|max:150', 'col' => 'col-md-6'],
                'data_solicitacao' => ['label' => 'Data da solicitação', 'type' => 'date', 'rules' => 'required|date', 'col' => 'col-md-3'],
                'observacoes' => ['label' => 'Observações', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
                'anexo' => ['label' => 'Anexo (pdf, imagem ou documento)', 'type' => 'file', 'accept' => 'application/pdf,image/*,.doc,.docx', 'col' => 'col-md-6', 'help' => 'Opcional. Arquivo de solicitação ou laudo.'],
            ],
        ]);
    }

    private function collect(): array
    {
        $data = [];
        foreach (['laboratorio', 'observacoes', 'prontuario_id', 'animal_id', 'cliente_id', 'veterinario_id', 'tipo', 'data_solicitacao'] as $field) {
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
            'veterinario_id' => 'nullable|integer',
            'prontuario_id' => 'nullable|integer',
            'tipo' => 'required|in:Hemograma,Bioquímico,Urina,Fezes,Ultrassonografia,Radiografia,Eletrocardiograma,Outros',
            'laboratorio' => 'nullable|max:150',
            'data_solicitacao' => 'required|date',
            'observacoes' => 'nullable',
        ]))->validate($data);

        foreach (['animal_id' => 'animais', 'cliente_id' => 'clientes'] as $field => $table) {
            if (!isset($errors[$field]) && Database::scalar('SELECT 1 FROM ' . Database::identifier($table) . ' WHERE id = :id LIMIT 1', ['id' => (int) $data[$field]]) === false) {
                $errors[$field] = 'Registro não encontrado.';
            }
        }
        if ($data['veterinario_id'] !== '' && Database::scalar('SELECT 1 FROM veterinarios WHERE id = :id LIMIT 1', ['id' => (int) $data['veterinario_id']]) === false) {
            $errors['veterinario_id'] = 'Veterinário não encontrado.';
        }
        if ($data['prontuario_id'] !== '' && Database::scalar('SELECT 1 FROM prontuarios WHERE id = :id LIMIT 1', ['id' => (int) $data['prontuario_id']]) === false) {
            $errors['prontuario_id'] = 'Prontuário não encontrado.';
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
