<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Services\AtendimentoService;
use PetCare\Validators\Validator;

final class AgendaController extends Controller
{
    private const TIPOS = [
        'Consulta' => 'Consulta',
        'Retorno' => 'Retorno',
        'Vacinação' => 'Vacinação',
        'Exame' => 'Exame',
        'Cirurgia' => 'Cirurgia',
        'Banho e tosa' => 'Banho e tosa',
    ];

    private const STATUS = [
        'aguardando' => 'Aguardando',
        'confirmado' => 'Confirmado',
        'em_atendimento' => 'Em atendimento',
        'finalizado' => 'Finalizado',
        'cancelado' => 'Cancelado',
        'falta' => 'Não compareceu',
    ];

    private const COLORS = [
        'confirmado' => '#198754',
        'aguardando' => '#ffc107',
        'em_atendimento' => '#0dcaf0',
        'finalizado' => '#0d6efd',
        'cancelado' => '#6c757d',
        'falta' => '#dc3545',
    ];

    private const CLASSES = [
        'confirmado' => 'success',
        'aguardando' => 'warning',
        'em_atendimento' => 'info',
        'finalizado' => 'primary',
        'cancelado' => 'secondary',
        'falta' => 'danger',
    ];

    public function index(): void
    {
        [$view, $date] = $this->periodInput();
        [$start, $end] = $this->periodBounds($view, $date);
        $status = $this->statusInput();
        $records = $this->records($start, $end, $status);

        $events = [];
        foreach ($records as $record) {
            $events[] = $this->event($record);
        }

        $stats = [
            'total' => count($records),
            'confirmados' => count(array_filter($records, static fn(array $row): bool => ($row['status'] ?? '') === 'confirmado')),
            'aguardando' => count(array_filter($records, static fn(array $row): bool => ($row['status'] ?? '') === 'aguardando')),
        ];

        $priorities = Database::fetchAll(
            'SELECT ag.id, ag.tipo, ag.data_agendamento, ag.hora_inicio, ag.status, ag.valor,
                    a.nome AS pet, c.nome AS tutor, v.nome AS veterinario
             FROM agendamentos ag
             INNER JOIN animais a ON a.id = ag.animal_id
             INNER JOIN clientes c ON c.id = ag.cliente_id
             INNER JOIN veterinarios v ON v.id = ag.veterinario_id
             WHERE ag.data_agendamento >= :hoje AND ag.status NOT IN (\'cancelado\',\'falta\')
             ORDER BY ag.data_agendamento ASC, ag.hora_inicio ASC
             LIMIT 5',
            ['hoje' => date('Y-m-d')]
        );

        $this->render('agenda/index', [
            'title' => 'Agenda',
            'events' => $events,
            'view' => $view,
            'date' => $date,
            'start' => $start,
            'end' => $end,
            'periodLabel' => $this->periodLabel($view, $date),
            'status' => $status,
            'statusLabel' => $status === '' ? '' : (self::STATUS[$status] ?? $status),
            'stats' => $stats,
            'priorities' => $priorities,
        ]);
    }

    public function events(): void
    {
        [$fallbackStart, $fallbackEnd] = $this->periodBounds('week', date('Y-m-d'));
        $start = $this->parseDate((string) Request::get('start', '')) ?? $fallbackStart;
        $end = $this->parseDate((string) Request::get('end', '')) ?? $fallbackEnd;
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $events = [];
        foreach ($this->records($start, $end) as $record) {
            $events[] = $this->event($record);
        }

        $this->json($events);
    }

    public function create(): void
    {
        $this->renderForm('Novo agendamento', $this->withOld($this->defaults()), [], 'agenda.store', 'Cadastrar agendamento');
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) Request::get('id', 0);
        $agendamento = $this->find($id);
        if ($agendamento === null) {
            abort(404, 'Agendamento não encontrado.');
        }

        $this->renderForm('Editar agendamento', $this->withOld($agendamento), [], 'agenda.update', 'Salvar alterações');
    }

    public function store(): void
    {
        $this->save(0);
    }

    public function update(): void
    {
        $id = (int) Request::post('id', 0);
        if ($this->find($id) === null) {
            abort(404, 'Agendamento não encontrado.');
        }

        $this->save($id);
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        $agendamento = $this->find($id);
        if ($agendamento === null) {
            abort(404, 'Agendamento não encontrado.');
        }

        Database::transaction(static fn(): int => Database::update('agendamentos', ['status' => 'cancelado'], 'id = :id', ['id' => $id]));
        log_action('cancelou_agendamento', 'agenda', $id, 'Agendamento ' . $agendamento['codigo'] . ' cancelado.');
        flash('success', 'Agendamento cancelado com sucesso.');
        $this->redirect('agenda.index');
    }

    public function delete(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        $agendamento = $this->find($id);
        if ($agendamento === null) {
            abort(404, 'Agendamento não encontrado.');
        }

        if (!in_array((string) ($agendamento['status'] ?? ''), ['finalizado', 'cancelado', 'falta'], true)) {
            flash('error', 'Somente agendamentos finalizados ou cancelados podem ser excluídos.');
            $this->redirect('agenda.index');
        }

        Database::transaction(static fn(): int => Database::delete('agendamentos', 'id = :id', ['id' => $id]));
        log_action('excluiu_agendamento', 'agenda', $id, 'Agendamento ' . $agendamento['codigo'] . ' excluído.');
        flash('success', 'Agendamento excluído com sucesso.');
        $this->redirect('agenda.index');
    }

    private function periodInput(): array
    {
        $view = (string) Request::get('view', 'week');
        if (!in_array($view, ['day', 'week', 'month'], true)) {
            $view = 'week';
        }
        $date = $this->parseDate((string) Request::get('date', '')) ?? date('Y-m-d');
        return [$view, $date];
    }

    private function periodBounds(string $view, string $date): array
    {
        $current = new \DateTimeImmutable($date . ' 00:00:00');
        if ($view === 'day') {
            return [$current->format('Y-m-d'), $current->format('Y-m-d')];
        }
        if ($view === 'month') {
            return [
                $current->modify('first day of this month')->format('Y-m-d'),
                $current->modify('last day of this month')->format('Y-m-d'),
            ];
        }
        $start = $current->modify('-' . ((int) $current->format('N') - 1) . ' days');
        return [$start->format('Y-m-d'), $start->modify('+6 days')->format('Y-m-d')];
    }

    private function periodLabel(string $view, string $date): string
    {
        $current = new \DateTimeImmutable($date . ' 00:00:00');
        $month = $this->monthName((int) $current->format('n'));
        if ($view === 'day') {
            return $current->format('d') . ' de ' . $month . ' de ' . $current->format('Y');
        }
        if ($view === 'month') {
            return ucfirst($month) . ' de ' . $current->format('Y');
        }
        $start = $current->modify('-' . ((int) $current->format('N') - 1) . ' days');
        $end = $start->modify('+6 days');
        return $start->format('d') . ' a ' . $end->format('d') . ' de ' . $this->monthName((int) $end->format('n')) . ' de ' . $end->format('Y');
    }

    private function monthName(int $month): string
    {
        $months = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
        return $months[$month] ?? '';
    }

    private function statusInput(): string
    {
        $status = (string) Request::get('status', '');
        return array_key_exists($status, self::STATUS) ? $status : '';
    }

    private function records(string $start, string $end, string $status = ''): array
    {
        if ($status !== '') {
            $where = 'ag.status = :status';
            $params = ['status' => $status];
        } else {
            $where = 'ag.data_agendamento BETWEEN :start AND :end';
            $params = ['start' => $start, 'end' => $end];
        }

        return Database::fetchAll(
            'SELECT ag.*, a.nome AS pet, c.nome AS tutor, v.nome AS veterinario
             FROM agendamentos ag
             INNER JOIN animais a ON a.id = ag.animal_id
             INNER JOIN clientes c ON c.id = ag.cliente_id
             INNER JOIN veterinarios v ON v.id = ag.veterinario_id
             WHERE ' . $where . '
             ORDER BY ag.data_agendamento ASC, ag.hora_inicio ASC, ag.id ASC',
            $params
        );
    }

    private function event(array $record): array
    {
        $status = (string) ($record['status'] ?? 'aguardando');
        $inicio = substr((string) ($record['hora_inicio'] ?? '00:00:00'), 0, 5);
        $fim = !empty($record['hora_fim'])
            ? substr((string) $record['hora_fim'], 0, 5)
            : $this->addMinutes($inicio . ':00', (int) ($record['duracao_minutos'] ?? 30), false);
        $color = self::COLORS[$status] ?? '#6c757d';

        return [
            'id' => (int) ($record['id'] ?? 0),
            'title' => trim((string) ($record['pet'] ?? '') . ' · ' . (string) ($record['tipo'] ?? '')),
            'start' => (string) ($record['data_agendamento'] ?? '') . 'T' . $inicio,
            'end' => (string) ($record['data_agendamento'] ?? '') . 'T' . $fim,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => in_array($status, ['aguardando', 'em_atendimento'], true) ? '#212529' : '#ffffff',
            'className' => 'agenda-evento border-' . (self::CLASSES[$status] ?? 'secondary'),
            'extendedProps' => [
                'tipo' => $record['tipo'] ?? '',
                'status' => $status,
                'pet' => $record['pet'] ?? '',
                'tutor' => $record['tutor'] ?? '',
                'veterinario' => $record['veterinario'] ?? '',
                'valor' => $record['valor'] ?? 0,
            ],
        ];
    }

    private function renderForm(string $title, array $values, array $errors, string $action, string $submitLabel): void
    {
        [$fields, $animaisPorCliente] = $this->formData();
        $this->render('agenda/form', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => $action,
            'cancelRoute' => 'agenda.index',
            'submitLabel' => $submitLabel,
            'fields' => $fields,
            'animaisPorCliente' => $animaisPorCliente,
        ]);
    }

    private function formData(): array
    {
        $clientes = [];
        foreach (Database::fetchAll('SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $clientes[(int) $row['id']] = $row['nome'];
        }

        $animais = Database::fetchAll(
            'SELECT a.id, a.cliente_id, a.nome, c.nome AS tutor
             FROM animais a INNER JOIN clientes c ON c.id = a.cliente_id
             WHERE a.ativo = 1 ORDER BY a.nome ASC'
        );
        $animaisOptions = [];
        $animaisPorCliente = [];
        foreach ($animais as $row) {
            $animaisOptions[(int) $row['id']] = $row['nome'] . ' · ' . $row['tutor'];
            $animaisPorCliente[(int) $row['cliente_id']][] = ['id' => (int) $row['id'], 'nome' => $row['nome'] . ' · ' . $row['tutor']];
        }

        $veterinarios = [];
        foreach (Database::fetchAll('SELECT id, nome FROM veterinarios WHERE ativo = 1 ORDER BY nome ASC') as $row) {
            $veterinarios[(int) $row['id']] = $row['nome'];
        }

        $fields = [
            'id' => ['label' => 'ID', 'type' => 'text', 'col' => 'd-none'],
            'codigo' => ['label' => 'Código', 'type' => 'text', 'rules' => 'required|max:50', 'col' => 'col-md-4', 'help' => 'Identificador do agendamento.'],
            'cliente_id' => ['label' => 'Tutor', 'type' => 'select', 'rules' => 'required|integer', 'options' => $clientes, 'col' => 'col-md-4'],
            'animal_id' => ['label' => 'Animal', 'type' => 'select', 'rules' => 'required|integer', 'options' => $animaisOptions, 'col' => 'col-md-4'],
            'veterinario_id' => ['label' => 'Veterinário', 'type' => 'select', 'rules' => 'required|integer', 'options' => $veterinarios, 'col' => 'col-md-4'],
            'tipo' => ['label' => 'Tipo', 'type' => 'select', 'rules' => 'required', 'options' => self::TIPOS, 'col' => 'col-md-4'],
            'status' => ['label' => 'Status', 'type' => 'select', 'rules' => 'required', 'options' => self::STATUS, 'col' => 'col-md-4'],
            'data_agendamento' => ['label' => 'Data', 'type' => 'date', 'rules' => 'required|date', 'col' => 'col-md-4'],
            'hora_inicio' => ['label' => 'Hora de início', 'type' => 'time', 'rules' => 'required', 'col' => 'col-md-4'],
            'duracao_minutos' => ['label' => 'Duração (minutos)', 'type' => 'number', 'rules' => 'required|integer', 'col' => 'col-md-4'],
            'valor' => ['label' => 'Valor (R$)', 'type' => 'number', 'rules' => 'required|numeric', 'col' => 'col-md-4'],
            'observacoes' => ['label' => 'Observações', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-12', 'rows' => 3],
        ];

        return [$fields, $animaisPorCliente];
    }

    private function defaults(): array
    {
        return [
            'id' => 0,
            'codigo' => gerar_codigo('AGE', 'agendamentos'),
            'cliente_id' => '',
            'animal_id' => '',
            'veterinario_id' => '',
            'tipo' => 'Consulta',
            'status' => 'aguardando',
            'data_agendamento' => date('Y-m-d'),
            'hora_inicio' => '09:00',
            'duracao_minutos' => 30,
            'valor' => '0',
            'observacoes' => '',
        ];
    }

    private function collect(): array
    {
        $data = [];
        foreach (['codigo', 'cliente_id', 'animal_id', 'veterinario_id', 'tipo', 'status', 'data_agendamento', 'hora_inicio', 'duracao_minutos', 'valor', 'observacoes'] as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : $value;
        }
        if ($data['observacoes'] === '') {
            $data['observacoes'] = null;
        }
        return $data;
    }

    private function validate(array $data, int $id): array
    {
        $errors = (new Validator([
            'codigo' => 'required|max:50|unique:agendamentos,codigo,id',
            'cliente_id' => 'required|integer',
            'animal_id' => 'required|integer',
            'veterinario_id' => 'required|integer',
            'tipo' => 'required|in:Consulta,Retorno,Vacinação,Exame,Cirurgia,Banho e tosa',
            'status' => 'required|in:aguardando,confirmado,em_atendimento,finalizado,cancelado,falta',
            'data_agendamento' => 'required|date',
            'hora_inicio' => 'required',
            'duracao_minutos' => 'required|integer',
            'valor' => 'required|numeric',
            'observacoes' => 'nullable',
        ]))->validate($data + ['id' => $id]);

        if ($this->parseDate((string) $data['data_agendamento']) === null) {
            $errors['data_agendamento'] = 'Informe uma data válida.';
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $data['hora_inicio'])) {
            $errors['hora_inicio'] = 'Informe um horário válido.';
        }
        if ((int) $data['duracao_minutos'] <= 0) {
            $errors['duracao_minutos'] = 'A duração deve ser maior que zero.';
        }
        if ((float) $data['valor'] < 0) {
            $errors['valor'] = 'O valor não pode ser negativo.';
        }
        if (!isset($errors['animal_id']) && !isset($errors['cliente_id'])) {
            $valid = Database::scalar(
                'SELECT 1 FROM animais WHERE id = :id AND cliente_id = :cliente AND ativo = 1 LIMIT 1',
                ['id' => (int) $data['animal_id'], 'cliente' => (int) $data['cliente_id']]
            );
            if ($valid === false) {
                $errors['animal_id'] = 'Animal inválido para o tutor selecionado.';
            }
        }

        return $errors;
    }

    private function hasConflict(int $veterinarioId, string $data, string $inicio, string $fim, int $ignoreId): bool
    {
        $count = Database::scalar(
            "SELECT COUNT(*) FROM agendamentos WHERE veterinario_id=:v AND data_agendamento=:d AND status NOT IN ('cancelado','falta') AND NOT (hora_fim <= :inicio OR hora_inicio >= :fim) AND id <> :ignore",
            ['v' => $veterinarioId, 'd' => $data, 'inicio' => $inicio, 'fim' => $fim, 'ignore' => $ignoreId]
        );

        return (int) $count > 0;
    }

    private function save(int $id): void
    {
        $data = $this->collect();
        if (($data['codigo'] ?? '') === '') {
            $data['codigo'] = gerar_codigo('AGE', 'agendamentos');
        }
        $errors = $this->validate($data, $id);
        $inicio = $this->normalizeTime((string) $data['hora_inicio']);
        $fim = $this->addMinutes($inicio, (int) $data['duracao_minutos']);

        if ($errors === [] && $this->hasConflict((int) $data['veterinario_id'], (string) $data['data_agendamento'], $inicio, $fim, $id)) {
            $message = 'Conflito de horário: este veterinário já possui agendamento no intervalo informado.';
            $errors['hora_inicio'] = $message;
            flash('error', $message);
        }

        if ($errors !== []) {
            set_old($data);
            $this->renderForm($id > 0 ? 'Editar agendamento' : 'Novo agendamento', $data, $errors, $id > 0 ? 'agenda.update' : 'agenda.store', $id > 0 ? 'Salvar alterações' : 'Cadastrar agendamento');
            return;
        }

        $payload = [
            'codigo' => $data['codigo'],
            'cliente_id' => (int) $data['cliente_id'],
            'animal_id' => (int) $data['animal_id'],
            'veterinario_id' => (int) $data['veterinario_id'],
            'tipo' => $data['tipo'],
            'data_agendamento' => $data['data_agendamento'],
            'hora_inicio' => $inicio,
            'hora_fim' => $fim,
            'duracao_minutos' => (int) $data['duracao_minutos'],
            'valor' => number_format((float) $data['valor'], 2, '.', ''),
            'observacoes' => $data['observacoes'],
            'status' => $data['status'],
        ];

        $savedId = Database::transaction(static function () use ($payload, $id): int {
            if ($id > 0) {
                Database::update('agendamentos', $payload, 'id = :id', ['id' => $id]);
                return $id;
            }
            return Database::insert('agendamentos', $payload);
        });

        $atendimentoCriado = $payload['status'] === 'finalizado' && AtendimentoService::createFromAgendamento($savedId) !== null;

        log_action($id > 0 ? 'editou_agendamento' : 'criou_agendamento', 'agenda', $savedId, 'Agendamento ' . $payload['codigo'] . ' salvo.');
        clear_old();
        flash('success', ($id > 0 ? 'Agendamento atualizado com sucesso.' : 'Agendamento cadastrado com sucesso.') . ($atendimentoCriado ? ' Atendimento finalizado registrado automaticamente.' : ''));
        $this->redirect('agenda.index');
    }

    private function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM agendamentos WHERE id = :id LIMIT 1', ['id' => $id]);
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

    private function normalizeTime(string $value): string
    {
        $value = substr($value, 0, 5);
        return preg_match('/^\d{2}:\d{2}$/', $value) === 1 ? $value . ':00' : '00:00:00';
    }

    private function addMinutes(string $time, int $minutes, bool $withSeconds = true): string
    {
        $base = \DateTimeImmutable::createFromFormat('!H:i:s', $this->normalizeTime($time));
        if ($base === false) {
            return $withSeconds ? '00:00:00' : '00:00';
        }
        $end = $base->modify(($minutes >= 0 ? '+' : '-') . abs($minutes) . ' minutes');
        return $end->format($withSeconds ? 'H:i:s' : 'H:i');
    }
}
