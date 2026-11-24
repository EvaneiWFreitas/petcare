<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Services\UploadService;
use PetCare\Validators\Validator;

final class ProntuarioController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $animalId = (int) Request::get('animal_id', 0);

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(a.nome LIKE :q OR c.nome LIKE :q OR p.diagnostico LIKE :q OR p.suspeita_clinica LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($animalId > 0) {
            $where[] = 'p.animal_id = :animal_id';
            $params['animal_id'] = $animalId;
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar(
            'SELECT COUNT(*) FROM prontuarios p
             INNER JOIN animais a ON a.id = p.animal_id
             INNER JOIN clientes c ON c.id = a.cliente_id' . $sqlWhere,
            $params
        );
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT p.id, p.data_atendimento, p.diagnostico, p.finalizado,
                    a.nome AS pet, c.nome AS tutor, v.nome AS veterinario
             FROM prontuarios p
             INNER JOIN animais a ON a.id = p.animal_id
             INNER JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN veterinarios v ON v.id = p.veterinario_id' . $sqlWhere . '
             ORDER BY p.data_atendimento DESC, p.id DESC
             LIMIT :limit OFFSET :offset',
            $params
        );

        $animais = Database::fetchAll(
            'SELECT a.id, a.nome, c.nome AS tutor FROM animais a
             INNER JOIN clientes c ON c.id = a.cliente_id
             ORDER BY a.nome ASC'
        );
        $extraFilters = '<div class="col-md-3 col-lg-2"><label for="animal_id" class="form-label small fw-semibold">Animal</label><select name="animal_id" id="animal_id" class="form-select"><option value="">Todos</option>';
        foreach ($animais as $animal) {
            $extraFilters .= '<option value="' . e($animal['id']) . '"' . selected($animalId, (int) $animal['id']) . '>' . e($animal['nome'] . ' · ' . $animal['tutor']) . '</option>';
        }
        $extraFilters .= '</select></div>';

        $this->render('prontuarios/index', [
            'title' => 'Prontuários',
            'rows' => $rows,
            'search' => $search,
            'animal_id' => $animalId,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'actions' => [
                ['label' => 'Visualizar', 'route' => 'prontuarios.show', 'icon' => 'eye', 'class' => 'btn-outline-primary'],
                ['label' => 'Editar', 'route' => 'prontuarios.edit', 'icon' => 'pen', 'class' => 'btn-outline-secondary'],
            ],
        ]);
    }

    public function create(): void
    {
        $animalId = (int) Request::get('animal_id', 0);
        $atendimentoId = (int) Request::get('atendimento_id', 0);
        $defaults = $this->defaults();
        $animalInfo = '';

        if ($animalId > 0) {
            $animal = Database::fetch(
                'SELECT a.id, a.nome, c.nome AS tutor FROM animais a
                 INNER JOIN clientes c ON c.id = a.cliente_id
                 WHERE a.id = :id LIMIT 1',
                ['id' => $animalId]
            );
            if ($animal === null) {
                flash('error', 'Animal não encontrado.');
                $this->redirect('prontuarios.index');
            }
            $defaults['animal_id'] = $animalId;
            $animalInfo = $animal['nome'] . ' · ' . $animal['tutor'];
        }

        if ($atendimentoId > 0) {
            $atendimento = Database::fetch(
                'SELECT * FROM atendimentos WHERE id = :id LIMIT 1',
                ['id' => $atendimentoId]
            );
            if ($atendimento === null) {
                flash('error', 'Atendimento não encontrado.');
                $this->redirect('atendimentos.index');
            }
            $defaults['atendimento_id'] = $atendimentoId;
            $defaults['animal_id'] = (int) $atendimento['animal_id'];
            $defaults['veterinario_id'] = (int) $atendimento['veterinario_id'];
            $defaults['data_atendimento'] = $this->toLocal((string) $atendimento['data_atendimento']);
            $defaults['anamnese'] = (string) ($atendimento['queixa_principal'] ?? '');
            if ($animalInfo === '') {
                $animalInfo = $this->animalInfo((int) $atendimento['animal_id']);
            }
        }

        $this->renderForm('Novo prontuário', $this->withOld($defaults), [], false, null, $animalInfo);
    }

    public function store(): void
    {
        $data = $this->collect();
        $errors = $this->validate($data, 0);
        $file = Request::file('anexo');
        $anexo = null;

        if ($errors === []) {
            try {
                $anexo = UploadService::handle($file, 'prontuarios', 'document');
            } catch (\RuntimeException $e) {
                $errors['anexo'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            $values = $data;
            $values['data_atendimento'] = $this->toLocal((string) $values['data_atendimento']);
            set_old($values);
            $this->renderForm('Novo prontuário', $values, $errors, false, null, $this->animalInfo((int) $data['animal_id']));
            return;
        }

        $payload = $this->payload($data);
        try {
            $id = Database::transaction(static function () use ($payload, $file, $anexo): int {
                $prontuarioId = Database::insert('prontuarios', $payload);
                if ($anexo !== null) {
                    Database::insert('anexos', [
                        'entidade' => 'prontuario',
                        'entidade_id' => $prontuarioId,
                        'nome_original' => (string) ($file['name'] ?? ''),
                        'caminho' => $anexo,
                        'mime' => (string) ($file['type'] ?? ''),
                        'tamanho' => (int) ($file['size'] ?? 0),
                        'usuario_id' => (int) (user()['id'] ?? 0) ?: null,
                    ]);
                }
                return $prontuarioId;
            });
        } catch (\Throwable $e) {
            if ($anexo !== null) {
                UploadService::delete($anexo);
            }
            throw $e;
        }

        log_action('criou_prontuario', 'prontuarios', $id, 'Prontuário registrado para o animal #' . $payload['animal_id'] . '.');
        clear_old();
        flash('success', 'Prontuário cadastrado com sucesso.');
        $this->redirectTo('prontuarios.show', ['id' => $id]);
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? (int) Request::get('id', 0);
        $prontuario = Database::fetch(
            'SELECT p.*, a.nome AS pet, c.id AS cliente_id, c.nome AS tutor, v.nome AS veterinario
             FROM prontuarios p
             INNER JOIN animais a ON a.id = p.animal_id
             INNER JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN veterinarios v ON v.id = p.veterinario_id
             WHERE p.id = :id LIMIT 1',
            ['id' => $id]
        );
        if ($prontuario === null) {
            abort(404, 'Prontuário não encontrado.');
        }

        $historico = Database::fetchAll(
            'SELECT p.id, p.data_atendimento, p.diagnostico, p.suspeita_clinica, p.finalizado, v.nome AS veterinario
             FROM prontuarios p LEFT JOIN veterinarios v ON v.id = p.veterinario_id
             WHERE p.animal_id = :animal
             ORDER BY p.data_atendimento DESC, p.id DESC',
            ['animal' => (int) $prontuario['animal_id']]
        );

        $auditorias = Database::fetchAll(
            'SELECT pa.*, COALESCE(u.nome, "Sistema") AS usuario
             FROM prontuario_auditorias pa LEFT JOIN usuarios u ON u.id = pa.usuario_id
             WHERE pa.prontuario_id = :id
             ORDER BY pa.created_at DESC, pa.id DESC',
            ['id' => $id]
        );
        foreach ($auditorias as &$auditoria) {
            $novos = json_decode((string) $auditoria['dados_novos'], true);
            $auditoria['justificativa'] = is_array($novos) ? (string) ($novos['justificativa'] ?? '') : '';
        }
        unset($auditoria);

        $anexos = Database::fetchAll(
            "SELECT * FROM anexos WHERE entidade = 'prontuario' AND entidade_id = :id ORDER BY created_at DESC, id DESC",
            ['id' => $id]
        );

        $sections = [
            'Identificação' => [
                'Animal' => $prontuario['pet'],
                'Tutor' => $prontuario['tutor'],
                'Veterinário' => $prontuario['veterinario'],
                'Data do atendimento' => date_br((string) $prontuario['data_atendimento'], true),
                'Atendimento vinculado' => (int) ($prontuario['atendimento_id'] ?? 0) > 0 ? '#' . $prontuario['atendimento_id'] : '—',
                'Finalizado' => static fn(): string => (int) ($prontuario['finalizado'] ?? 0) === 1 ? status_badge('finalizado') : '<span class="badge bg-secondary rounded-pill">Em aberto</span>',
                'Criado em' => date_br((string) $prontuario['created_at'], true),
                'Atualizado em' => date_br((string) $prontuario['updated_at'], true),
            ],
            'Histórico e anamnese' => [
                'Histórico clínico' => $prontuario['historico_clinico'],
                'Anamnese' => $prontuario['anamnese'],
                'Exame físico' => $prontuario['exame_fisico'],
            ],
            'Sinais vitais e medidas' => [
                'Temperatura' => $this->measurement($prontuario['temperatura'] ?? null, '°C'),
                'Frequência cardíaca' => $this->measurement($prontuario['frequencia_cardiaca'] ?? null, 'bpm'),
                'Frequência respiratória' => $this->measurement($prontuario['frequencia_respiratoria'] ?? null, 'irpm'),
                'Peso' => $this->measurement($prontuario['peso'] ?? null, 'kg'),
                'Altura' => $this->measurement($prontuario['altura'] ?? null, 'cm'),
            ],
            'Diagnóstico e conduta' => [
                'Diagnóstico' => $prontuario['diagnostico'],
                'Suspeita clínica' => $prontuario['suspeita_clinica'],
                'Procedimentos' => $prontuario['procedimentos'],
                'Recomendações' => $prontuario['recomendacoes'],
                'Retorno recomendado' => date_br((string) ($prontuario['retorno_recomendado'] ?? '')),
            ],
            'Prescrição' => [
                'Medicamentos' => $prontuario['medicamentos'],
                'Dosagem' => $prontuario['dosagem'],
                'Frequência' => $prontuario['frequencia'],
                'Duração do tratamento' => $prontuario['duracao_tratamento'],
            ],
        ];

        $this->render('prontuarios/show', [
            'title' => 'Detalhes do prontuário',
            'prontuario' => $prontuario,
            'historico' => $historico,
            'auditorias' => $auditorias,
            'anexos' => $anexos,
            'sections' => $sections,
            'actions' => [
                ['label' => 'Editar', 'route' => 'prontuarios.edit', 'icon' => 'pen', 'class' => 'btn-outline-primary'],
                ['label' => 'Voltar', 'route' => 'prontuarios.index', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary'],
            ],
            'recordId' => $id,
        ]);
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) Request::get('id', 0);
        $prontuario = $this->find($id);
        if ($prontuario === null) {
            abort(404, 'Prontuário não encontrado.');
        }

        $values = $this->withOld(array_merge($this->defaults(), $prontuario));
        $values['data_atendimento'] = $this->toLocal((string) $values['data_atendimento']);
        $warning = (int) $prontuario['finalizado'] === 1
            ? 'Este prontuário está finalizado. Toda alteração será registrada na auditoria e exige justificativa.'
            : null;

        $this->renderForm('Editar prontuário', $values, [], true, $warning, $this->animalInfo((int) $prontuario['animal_id']));
    }

    public function update(): void
    {
        $id = (int) Request::post('id', 0);
        $antes = $this->find($id);
        if ($antes === null) {
            abort(404, 'Prontuário não encontrado.');
        }

        $data = $this->collect();
        $errors = $this->validate($data, $id);
        $finalizadoAntes = (int) $antes['finalizado'] === 1;
        if ($finalizadoAntes && trim((string) ($data['justificativa'] ?? '')) === '') {
            $errors['justificativa'] = 'Informe a justificativa da alteração em prontuário finalizado.';
        }

        if ($errors !== []) {
            $values = array_merge($this->defaults(), $antes, $data);
            $values['data_atendimento'] = $this->toLocal((string) $values['data_atendimento']);
            $warning = $finalizadoAntes ? 'Este prontuário está finalizado. Toda alteração será registrada na auditoria e exige justificativa.' : null;
            set_old($values);
            $this->renderForm('Editar prontuário', $values, $errors, true, $warning, $this->animalInfo((int) $antes['animal_id']));
            return;
        }

        $data['finalizado'] = $finalizadoAntes ? 1 : (int) $data['finalizado'];
        $payload = $this->payload($data);
        $novos = $payload + ['justificativa' => trim((string) $data['justificativa'])];
        $acao = $finalizadoAntes ? 'edicao_prontuario_finalizado' : 'edicao_prontuario';

        Database::transaction(static function () use ($id, $antes, $payload, $novos, $acao): void {
            Database::insert('prontuario_auditorias', [
                'prontuario_id' => $id,
                'usuario_id' => (int) (user()['id'] ?? 0),
                'acao' => $acao,
                'dados_anteriores' => json_encode($antes, JSON_UNESCAPED_UNICODE),
                'dados_novos' => json_encode($novos, JSON_UNESCAPED_UNICODE),
            ]);
            Database::update('prontuarios', $payload, 'id = :id', ['id' => $id]);
        });

        log_action('editou_prontuario', 'prontuarios', $id, 'Prontuário #' . $id . ' atualizado.');
        clear_old();
        flash('success', 'Prontuário atualizado com sucesso.');
        $this->redirectTo('prontuarios.show', ['id' => $id]);
    }

    private function renderForm(string $title, array $values, array $errors, bool $editing, ?string $warning, string $animalInfo): void
    {
        $this->render('prontuarios/form', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => $editing ? 'prontuarios.update' : 'prontuarios.store',
            'cancelRoute' => $editing ? 'prontuarios.show' : 'prontuarios.index',
            'submitLabel' => $editing ? 'Salvar alterações' : 'Cadastrar prontuário',
            'fields' => $this->fields($editing, (int) ($values['finalizado'] ?? 0), $animalInfo),
            'enctype' => $editing ? null : 'multipart/form-data',
            'warning' => $warning,
        ]);
    }

    private function fields(bool $editing, int $finalizado, string $animalInfo): array
    {
        $animais = [];
        foreach (Database::fetchAll(
            'SELECT a.id, a.nome, c.nome AS tutor FROM animais a
             INNER JOIN clientes c ON c.id = a.cliente_id
             ORDER BY a.nome ASC'
        ) as $row) {
            $animais[(int) $row['id']] = $row['nome'] . ' · ' . $row['tutor'];
        }
        $veterinarios = [];
        foreach (Database::fetchAll('SELECT id, nome FROM veterinarios WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $veterinarios[(int) $row['id']] = $row['nome'];
        }

        $fields = [
            'id' => ['label' => 'ID', 'type' => 'text', 'col' => 'd-none'],
            'atendimento_id' => ['label' => 'Atendimento', 'type' => 'text', 'col' => 'd-none'],
            'animal_id' => [
                'label' => 'Animal',
                'type' => 'select',
                'rules' => 'required|integer',
                'options' => $animais,
                'col' => 'col-md-6',
                'help' => $animalInfo !== '' ? 'Selecionado: ' . $animalInfo : 'Selecione o animal e o tutor.',
            ],
            'veterinario_id' => ['label' => 'Veterinário', 'type' => 'select', 'rules' => 'required|integer', 'options' => $veterinarios, 'col' => 'col-md-6'],
            'data_atendimento' => ['label' => 'Data e hora do atendimento', 'type' => 'text', 'rules' => 'required|datetime', 'col' => 'col-md-4'],
            'historico_clinico' => ['label' => 'Histórico clínico', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'anamnese' => ['label' => 'Anamnese', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'exame_fisico' => ['label' => 'Exame físico', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'temperatura' => ['label' => 'Temperatura (°C)', 'type' => 'number', 'rules' => 'nullable|numeric', 'col' => 'col-md-3'],
            'frequencia_cardiaca' => ['label' => 'Frequência cardíaca', 'type' => 'number', 'rules' => 'nullable|integer', 'col' => 'col-md-3'],
            'frequencia_respiratoria' => ['label' => 'Frequência respiratória', 'type' => 'number', 'rules' => 'nullable|integer', 'col' => 'col-md-3'],
            'peso' => ['label' => 'Peso (kg)', 'type' => 'number', 'rules' => 'nullable|numeric', 'col' => 'col-md-3'],
            'altura' => ['label' => 'Altura (cm)', 'type' => 'number', 'rules' => 'nullable|numeric', 'col' => 'col-md-3'],
            'diagnostico' => ['label' => 'Diagnóstico', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'suspeita_clinica' => ['label' => 'Suspeita clínica', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'procedimentos' => ['label' => 'Procedimentos', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'medicamentos' => ['label' => 'Medicamentos', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6', 'rows' => 3],
            'dosagem' => ['label' => 'Dosagem', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6', 'rows' => 3],
            'frequencia' => ['label' => 'Frequência', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6', 'rows' => 3],
            'duracao_tratamento' => ['label' => 'Duração do tratamento', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6', 'rows' => 3],
            'recomendacoes' => ['label' => 'Recomendações', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            'retorno_recomendado' => ['label' => 'Retorno recomendado', 'type' => 'date', 'rules' => 'nullable|date', 'col' => 'col-md-4'],
            'finalizado' => ['label' => 'Finalizado', 'type' => 'checkbox', 'col' => 'col-md-4'],
        ];

        if ($finalizado === 1) {
            $fields['justificativa'] = ['label' => 'Justificativa da alteração', 'type' => 'textarea', 'rules' => 'required', 'col' => 'col-md-12', 'rows' => 3, 'help' => 'Obrigatória para prontuários finalizados e registrada na auditoria.'];
        }
        if (!$editing) {
            $fields['anexo'] = ['label' => 'Anexo (PDF ou imagem)', 'type' => 'file', 'accept' => 'application/pdf,image/*', 'col' => 'col-md-6', 'help' => 'Opcional. Tamanho máximo definido pelo sistema.'];
        }

        return $fields;
    }

    private function defaults(): array
    {
        return [
            'id' => 0,
            'atendimento_id' => 0,
            'animal_id' => '',
            'veterinario_id' => '',
            'data_atendimento' => date('Y-m-d\TH:i'),
            'historico_clinico' => '',
            'anamnese' => '',
            'exame_fisico' => '',
            'temperatura' => '',
            'frequencia_cardiaca' => '',
            'frequencia_respiratoria' => '',
            'peso' => '',
            'altura' => '',
            'diagnostico' => '',
            'suspeita_clinica' => '',
            'procedimentos' => '',
            'medicamentos' => '',
            'dosagem' => '',
            'frequencia' => '',
            'duracao_tratamento' => '',
            'recomendacoes' => '',
            'retorno_recomendado' => '',
            'finalizado' => 1,
            'justificativa' => '',
            'anexo' => '',
        ];
    }

    private function collect(): array
    {
        $data = [];
        foreach (['historico_clinico', 'anamnese', 'exame_fisico', 'diagnostico', 'suspeita_clinica', 'procedimentos', 'medicamentos', 'dosagem', 'frequencia', 'duracao_tratamento', 'recomendacoes', 'justificativa'] as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : $value;
            if ($data[$field] === '') {
                $data[$field] = null;
            }
        }
        foreach (['atendimento_id', 'veterinario_id', 'frequencia_cardiaca', 'frequencia_respiratoria'] as $field) {
            $value = trim((string) Request::post($field, ''));
            $data[$field] = $value === '' ? null : (int) $value;
        }
        foreach (['temperatura', 'peso', 'altura'] as $field) {
            $value = trim((string) Request::post($field, ''));
            $data[$field] = $value === '' ? null : $value;
        }
        $value = trim((string) Request::post('animal_id', ''));
        $data['animal_id'] = $value === '' ? null : (int) $value;
        $data['retorno_recomendado'] = trim((string) Request::post('retorno_recomendado', '')) ?: null;
        $data['data_atendimento'] = $this->normalizeDateTime((string) Request::post('data_atendimento', ''));
        $data['finalizado'] = Request::has('finalizado') && (int) Request::post('finalizado', 0) === 1 ? 1 : 0;
        $data['atendimento_id'] = (int) ($data['atendimento_id'] ?? 0);
        return $data;
    }

    private function validate(array $data, int $id): array
    {
        $errors = (new Validator([
            'animal_id' => 'required|integer',
            'veterinario_id' => 'required|integer',
            'atendimento_id' => 'nullable|integer',
            'data_atendimento' => 'required|datetime',
            'temperatura' => 'nullable|numeric',
            'frequencia_cardiaca' => 'nullable|integer',
            'frequencia_respiratoria' => 'nullable|integer',
            'peso' => 'nullable|numeric',
            'altura' => 'nullable|numeric',
            'retorno_recomendado' => 'nullable|date',
            'finalizado' => 'required|in:0,1',
        ]))->validate($data + ['id' => $id]);

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $data['data_atendimento']);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if ($parsed === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $parsed->format('Y-m-d H:i:s') !== $data['data_atendimento']) {
            $errors['data_atendimento'] = 'Informe data e hora válidas.';
        }
        if (!empty($data['retorno_recomendado']) && $this->parseDate((string) $data['retorno_recomendado']) === null) {
            $errors['retorno_recomendado'] = 'Informe uma data válida.';
        }
        if (!isset($errors['animal_id']) && Database::scalar('SELECT 1 FROM animais WHERE id = :id AND ativo = 1 LIMIT 1', ['id' => (int) $data['animal_id']]) === false) {
            $errors['animal_id'] = 'Animal não encontrado.';
        }
        if (!isset($errors['veterinario_id']) && Database::scalar('SELECT 1 FROM veterinarios WHERE id = :id AND ativo = 1 LIMIT 1', ['id' => (int) $data['veterinario_id']]) === false) {
            $errors['veterinario_id'] = 'Veterinário não encontrado.';
        }
        if ((int) $data['atendimento_id'] > 0 && Database::scalar('SELECT 1 FROM atendimentos WHERE id = :id LIMIT 1', ['id' => (int) $data['atendimento_id']]) === false) {
            $errors['atendimento_id'] = 'Atendimento não encontrado.';
        }

        return $errors;
    }

    private function payload(array $data): array
    {
        return [
            'atendimento_id' => (int) $data['atendimento_id'] > 0 ? (int) $data['atendimento_id'] : null,
            'animal_id' => (int) $data['animal_id'],
            'veterinario_id' => (int) $data['veterinario_id'],
            'data_atendimento' => $data['data_atendimento'],
            'historico_clinico' => $data['historico_clinico'],
            'anamnese' => $data['anamnese'],
            'exame_fisico' => $data['exame_fisico'],
            'temperatura' => $data['temperatura'],
            'frequencia_cardiaca' => $data['frequencia_cardiaca'],
            'frequencia_respiratoria' => $data['frequencia_respiratoria'],
            'peso' => $data['peso'],
            'altura' => $data['altura'],
            'diagnostico' => $data['diagnostico'],
            'suspeita_clinica' => $data['suspeita_clinica'],
            'procedimentos' => $data['procedimentos'],
            'medicamentos' => $data['medicamentos'],
            'dosagem' => $data['dosagem'],
            'frequencia' => $data['frequencia'],
            'duracao_tratamento' => $data['duracao_tratamento'],
            'recomendacoes' => $data['recomendacoes'],
            'retorno_recomendado' => $data['retorno_recomendado'] ?: null,
            'finalizado' => (int) $data['finalizado'],
        ];
    }

    private function measurement(mixed $value, string $unit): ?string
    {
        return $value === null || $value === '' ? null : e($value . ' ' . $unit);
    }

    private function animalInfo(int $animalId): string
    {
        if ($animalId <= 0) {
            return '';
        }
        $row = Database::fetch(
            'SELECT a.nome, c.nome AS tutor FROM animais a
             INNER JOIN clientes c ON c.id = a.cliente_id
             WHERE a.id = :id LIMIT 1',
            ['id' => $animalId]
        );
        return $row === null ? '' : $row['nome'] . ' · ' . $row['tutor'];
    }

    private function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM prontuarios WHERE id = :id LIMIT 1', ['id' => $id]);
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

    private function parseDate(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            return null;
        }
        return $value;
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
