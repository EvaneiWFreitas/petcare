<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class VacinacaoController extends Controller
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
            $where[] = '(a.nome LIKE :q OR c.nome LIKE :q OR vac.nome LIKE :q OR vc.lote LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($animalId > 0) {
            $where[] = 'vc.animal_id = :animal_id';
            $params['animal_id'] = $animalId;
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $joins = ' FROM vacinacoes vc
                   INNER JOIN animais a ON a.id = vc.animal_id
                   INNER JOIN clientes c ON c.id = vc.cliente_id
                   INNER JOIN vacinas vac ON vac.id = vc.vacina_id
                   LEFT JOIN veterinarios v ON v.id = vc.veterinario_id';

        $total = (int) Database::scalar('SELECT COUNT(*)' . $joins . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];

        $rows = Database::fetchAll(
            'SELECT vc.id, vc.animal_id, vc.lote, vc.data_aplicacao, vc.proxima_dose,
                    a.nome AS pet, c.nome AS tutor, vac.nome AS vacina, v.nome AS veterinario' . $joins . $sqlWhere . '
             ORDER BY vc.data_aplicacao DESC, vc.id DESC
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

        $alertas = [
            'atrasadas' => (int) Database::scalar("SELECT COUNT(*) FROM vacinacoes WHERE proxima_dose IS NOT NULL AND proxima_dose < CURDATE()"),
            'proximas' => (int) Database::scalar("SELECT COUNT(*) FROM vacinacoes WHERE proxima_dose IS NOT NULL AND proxima_dose >= CURDATE() AND proxima_dose <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"),
        ];

        $this->render('vacinas/index', [
            'title' => 'Vacinações',
            'rows' => $rows,
            'columns' => [
                'Pet' => 'pet',
                'Tutor' => 'tutor',
                'Vacina' => 'vacina',
                'Lote' => 'lote',
                'Aplicação' => static fn(array $row): string => e(date_br((string) ($row['data_aplicacao'] ?? ''))),
                'Próxima dose' => fn(array $row): string => $this->doseBadge($row['proxima_dose'] ?? null),
                'Veterinário' => 'veterinario',
            ],
            'route' => 'vacinas',
            'createRoute' => 'vacinas.create',
            'search' => $search,
            'animal_id' => $animalId,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'alertas' => $alertas,
            'actions' => [
                ['label' => 'Carteira', 'route' => 'vacinas.carteira', 'icon' => 'book-medical', 'class' => 'btn-outline-primary'],
            ],
        ]);
    }

    public function create(): void
    {
        $defaults = [
            'animal_id' => (int) Request::get('animal_id', 0) ?: '',
            'cliente_id' => (int) Request::get('cliente_id', 0) ?: '',
            'vacina_id' => '',
            'veterinario_id' => '',
            'lote' => '',
            'data_aplicacao' => date('Y-m-d'),
            'proxima_dose' => '',
            'observacoes' => '',
        ];

        $this->renderForm('Nova vacinação', $this->withOld($defaults), []);
    }

    public function store(): void
    {
        $data = $this->collect();
        $errors = $this->validate($data);

        if ($errors !== []) {
            set_old($data);
            $this->renderForm('Nova vacinação', $data, $errors);
            return;
        }

        $payload = [
            'animal_id' => (int) $data['animal_id'],
            'cliente_id' => (int) $data['cliente_id'],
            'vacina_id' => (int) $data['vacina_id'],
            'veterinario_id' => $data['veterinario_id'] === '' ? null : (int) $data['veterinario_id'],
            'lote' => $data['lote'] === '' ? null : $data['lote'],
            'data_aplicacao' => $data['data_aplicacao'],
            'proxima_dose' => $data['proxima_dose'] === '' ? null : $data['proxima_dose'],
            'observacoes' => $data['observacoes'] === '' ? null : $data['observacoes'],
        ];

        $id = Database::transaction(static fn(): int => Database::insert('vacinacoes', $payload));

        log_action('aplicou_vacina', 'vacinas', $id, 'Vacina aplicada ao animal #' . $payload['animal_id'] . '.');
        clear_old();
        flash('success', 'Vacinação registrada com sucesso.');
        $this->redirect('vacinas.index');
    }

    public function carteira(): void
    {
        \view('vacinas/carteira', $this->carteiraData());
    }

    public function printCarteira(): void
    {
        \view('vacinas/carteira', $this->carteiraData(), 'print');
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
        $vacinas = [];
        foreach (Database::fetchAll('SELECT id, nome, fabricante FROM vacinas ORDER BY nome ASC') as $row) {
            $vacinas[(int) $row['id']] = $row['nome'] . ($row['fabricante'] ? ' · ' . $row['fabricante'] : '');
        }
        $veterinarios = [];
        foreach (Database::fetchAll('SELECT id, nome FROM veterinarios WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $veterinarios[(int) $row['id']] = $row['nome'];
        }

        $this->render('vacinas/form', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => 'vacinas.store',
            'cancelRoute' => 'vacinas.index',
            'submitLabel' => 'Registrar vacinação',
            'fields' => [
                'animal_id' => ['label' => 'Animal', 'type' => 'select', 'rules' => 'required|integer', 'options' => $animais, 'col' => 'col-md-6'],
                'cliente_id' => ['label' => 'Tutor', 'type' => 'select', 'rules' => 'required|integer', 'options' => $clientes, 'col' => 'col-md-6'],
                'vacina_id' => ['label' => 'Vacina', 'type' => 'select', 'rules' => 'required|integer', 'options' => $vacinas, 'col' => 'col-md-6'],
                'veterinario_id' => ['label' => 'Veterinário', 'type' => 'select', 'rules' => 'nullable|integer', 'options' => $veterinarios, 'col' => 'col-md-6', 'empty' => 'Não informar'],
                'lote' => ['label' => 'Lote', 'type' => 'text', 'rules' => 'nullable|max:80', 'col' => 'col-md-4'],
                'data_aplicacao' => ['label' => 'Data da aplicação', 'type' => 'date', 'rules' => 'required|date', 'col' => 'col-md-4'],
                'proxima_dose' => ['label' => 'Próxima dose', 'type' => 'date', 'rules' => 'nullable|date', 'col' => 'col-md-4'],
                'observacoes' => ['label' => 'Observações', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
            ],
        ]);
    }

    private function collect(): array
    {
        $data = [];
        foreach (['lote', 'observacoes'] as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : '';
        }
        foreach (['animal_id', 'cliente_id', 'vacina_id', 'veterinario_id'] as $field) {
            $data[$field] = trim((string) Request::post($field, ''));
        }
        foreach (['data_aplicacao', 'proxima_dose'] as $field) {
            $data[$field] = trim((string) Request::post($field, ''));
        }
        return $data;
    }

    private function validate(array $data): array
    {
        $errors = (new Validator([
            'animal_id' => 'required|integer',
            'cliente_id' => 'required|integer',
            'vacina_id' => 'required|integer',
            'veterinario_id' => 'nullable|integer',
            'lote' => 'nullable|max:80',
            'data_aplicacao' => 'required|date',
            'proxima_dose' => 'nullable|date',
            'observacoes' => 'nullable',
        ]))->validate($data);

        if ($data['proxima_dose'] !== '' && $data['data_aplicacao'] !== '' && $data['proxima_dose'] < $data['data_aplicacao']) {
            $errors['proxima_dose'] = 'A próxima dose não pode ser anterior à data de aplicação.';
        }
        foreach (['animal_id' => 'animais', 'cliente_id' => 'clientes', 'vacina_id' => 'vacinas'] as $field => $table) {
            if (!isset($errors[$field]) && Database::scalar('SELECT 1 FROM ' . Database::identifier($table) . ' WHERE id = :id LIMIT 1', ['id' => (int) $data[$field]]) === false) {
                $errors[$field] = 'Registro não encontrado.';
            }
        }
        if ($data['veterinario_id'] !== '' && Database::scalar('SELECT 1 FROM veterinarios WHERE id = :id LIMIT 1', ['id' => (int) $data['veterinario_id']]) === false) {
            $errors['veterinario_id'] = 'Veterinário não encontrado.';
        }
        return $errors;
    }

    private function carteiraData(): array
    {
        $animalId = (int) Request::get('animal_id', 0);
        $clienteId = (int) Request::get('cliente_id', 0);
        if ($animalId <= 0 && $clienteId <= 0) {
            abort(404, 'Informe um animal ou tutor para consultar a carteira.');
        }

        $columns = 'a.id, a.nome, a.codigo, a.data_nascimento, c.id AS tutor_id, c.nome AS tutor_nome, c.telefone AS tutor_telefone, c.email AS tutor_email';
        if ($animalId > 0) {
            $animal = Database::fetch(
                'SELECT ' . $columns . ' FROM animais a
                 INNER JOIN clientes c ON c.id = a.cliente_id
                 WHERE a.id = :id LIMIT 1',
                ['id' => $animalId]
            );
            if ($animal === null) {
                abort(404, 'Animal não encontrado.');
            }
            $clienteId = (int) $animal['tutor_id'];
        } else {
            $animal = Database::fetch(
                'SELECT ' . $columns . ' FROM animais a
                 INNER JOIN clientes c ON c.id = a.cliente_id
                 WHERE a.cliente_id = :id
                 ORDER BY a.nome ASC LIMIT 1',
                ['id' => $clienteId]
            );
            if ($animal === null) {
                abort(404, 'Nenhum animal encontrado para este tutor.');
            }
            $animalId = (int) $animal['id'];
        }

        $vacinacoes = Database::fetchAll(
            'SELECT vc.id, vc.lote, vc.data_aplicacao, vc.proxima_dose, vc.observacoes,
                    vac.nome AS vacina, vac.fabricante, v.nome AS veterinario
             FROM vacinacoes vc
             INNER JOIN vacinas vac ON vac.id = vc.vacina_id
             LEFT JOIN veterinarios v ON v.id = vc.veterinario_id
             WHERE vc.animal_id = :animal OR vc.cliente_id = :cliente
             ORDER BY vc.data_aplicacao DESC, vc.id DESC',
            ['animal' => $animalId, 'cliente' => $clienteId]
        );
        foreach ($vacinacoes as &$vacinacao) {
            $status = $this->doseStatus($vacinacao['proxima_dose'] ?? null);
            $vacinacao['status_label'] = $status['label'];
            $vacinacao['status_class'] = $status['class'];
        }
        unset($vacinacao);

        return [
            'title' => 'Carteira de vacinação',
            'animal' => $animal,
            'tutor' => [
                'id' => $clienteId,
                'nome' => $animal['tutor_nome'],
                'telefone' => $animal['tutor_telefone'],
                'email' => $animal['tutor_email'],
            ],
            'vacinacoes' => $vacinacoes,
        ];
    }

    private function doseStatus(?string $date): array
    {
        if ($date === null || $date === '') {
            return ['label' => 'Sem dose', 'class' => 'secondary'];
        }
        $today = new \DateTimeImmutable('today');
        $next = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($next === false) {
            return ['label' => 'Inválida', 'class' => 'secondary'];
        }
        if ($next < $today) {
            return ['label' => 'Atrasada', 'class' => 'danger'];
        }
        if ($next <= $today->modify('+30 days')) {
            return ['label' => 'Próxima', 'class' => 'warning text-dark'];
        }
        return ['label' => 'Em dia', 'class' => 'success'];
    }

    private function doseBadge(?string $date): string
    {
        if ($date === null || $date === '') {
            return '<span class="text-muted">—</span>';
        }
        $status = $this->doseStatus($date);
        return '<span class="badge bg-' . e($status['class']) . ' rounded-pill">' . e(date_br($date)) . ' · ' . e($status['label']) . '</span>';
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
