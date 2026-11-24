<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class FinanceiroController extends Controller
{
    private const PER_PAGE = 15;

    private const ORIGENS = [
        'Consultas' => 'Consultas',
        'Vacinas' => 'Vacinas',
        'Exames' => 'Exames',
        'Cirurgias' => 'Cirurgias',
        'Internações' => 'Internações',
        'Medicamentos' => 'Medicamentos',
        'Produtos' => 'Produtos',
        'Outros serviços' => 'Outros serviços',
    ];

    private const CATEGORIAS = [
        'Fornecedores' => 'Fornecedores',
        'Aluguel' => 'Aluguel',
        'Água' => 'Água',
        'Energia' => 'Energia',
        'Internet' => 'Internet',
        'Salários' => 'Salários',
        'Compras' => 'Compras',
        'Despesas administrativas' => 'Despesas administrativas',
    ];

    private const STATUS = [
        'aberto' => 'Em aberto',
        'pago' => 'Pago',
        'parcial' => 'Parcial',
        'vencido' => 'Vencido',
    ];

    private const FORMAS_PAGAMENTO = [
        'Dinheiro' => 'Dinheiro',
        'PIX' => 'PIX',
        'Cartão de crédito' => 'Cartão de crédito',
        'Cartão de débito' => 'Cartão de débito',
        'Transferência bancária' => 'Transferência bancária',
        'Outros' => 'Outros',
    ];

    public function index(): void
    {
        $receitasDia = (float) Database::scalar('SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE conta_receber_id IS NOT NULL AND DATE(data_pagamento) = CURDATE()');
        $receitasMes = (float) Database::scalar('SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE conta_receber_id IS NOT NULL AND YEAR(data_pagamento) = YEAR(CURDATE()) AND MONTH(data_pagamento) = MONTH(CURDATE())');
        $despesasMes = (float) Database::scalar('SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE conta_pagar_id IS NOT NULL AND YEAR(data_pagamento) = YEAR(CURDATE()) AND MONTH(data_pagamento) = MONTH(CURDATE())')
            + (float) Database::scalar('SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE YEAR(data_despesa) = YEAR(CURDATE()) AND MONTH(data_despesa) = MONTH(CURDATE())');

        $stats = [
            'receitas_dia' => $receitasDia,
            'receitas_mes' => $receitasMes,
            'despesas_mes' => $despesasMes,
            'saldo' => $receitasMes - $despesasMes,
            'contas_receber' => (int) Database::scalar("SELECT COUNT(*) FROM contas_receber WHERE status IN ('aberto','parcial','vencido')"),
            'contas_pagar' => (int) Database::scalar("SELECT COUNT(*) FROM contas_pagar WHERE status IN ('aberto','parcial','vencido')"),
            'vencidas' => (int) Database::scalar("SELECT COUNT(*) FROM contas_receber WHERE vencimento < CURDATE() AND status <> 'pago'")
                + (int) Database::scalar("SELECT COUNT(*) FROM contas_pagar WHERE vencimento < CURDATE() AND status <> 'pago'"),
        ];

        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = (int) date('m', strtotime("-{$i} months"));
            $year = (int) date('Y', strtotime("-{$i} months"));
            $labels[] = [
                'label' => strtoupper(mb_substr($this->monthName($month), 0, 3)) . '/' . substr((string) $year, -2),
                'key' => sprintf('%04d-%02d', $year, $month),
            ];
        }
        $receitas = [];
        $despesas = [];
        foreach ($labels as $item) {
            $receitas[] = (float) Database::scalar('SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE conta_receber_id IS NOT NULL AND DATE_FORMAT(data_pagamento, "%Y-%m") = :key', ['key' => $item['key']]);
            $despesas[] = (float) Database::scalar('SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE conta_pagar_id IS NOT NULL AND DATE_FORMAT(data_pagamento, "%Y-%m") = :key', ['key' => $item['key']])
                + (float) Database::scalar('SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE DATE_FORMAT(data_despesa, "%Y-%m") = :key', ['key' => $item['key']]);
        }
        $chart = [
            'labels' => array_column($labels, 'label'),
            'receitas' => $receitas,
            'despesas' => $despesas,
        ];

        $payments = Database::fetchAll(
            'SELECT pg.id, pg.valor, pg.forma_pagamento, pg.data_pagamento,
                    COALESCE(c.nome, f.nome, "—") AS participante,
                    CASE WHEN pg.conta_receber_id IS NOT NULL THEN "Recebimento" ELSE "Pagamento" END AS tipo
             FROM pagamentos pg
             LEFT JOIN contas_receber cr ON cr.id = pg.conta_receber_id
             LEFT JOIN clientes c ON c.id = cr.cliente_id
             LEFT JOIN contas_pagar cp ON cp.id = pg.conta_pagar_id
             LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
             ORDER BY pg.data_pagamento DESC, pg.id DESC LIMIT 8'
        );

        $shortcuts = [];
        $definitions = [
            ['Receber', 'financeiro.receber', 'arrow-down-to-line', 'success'],
            ['Pagar', 'financeiro.pagar', 'arrow-up-to-line', 'danger'],
            ['Nova conta a receber', 'financeiro.create-receber', 'hand-holding-dollar', 'primary'],
            ['Nova conta a pagar', 'financeiro.create-pagar', 'file-invoice-dollar', 'warning'],
            ['Fluxo de caixa', 'financeiro.fluxo', 'chart-line', 'info'],
        ];
        foreach ($definitions as [$label, $route, $icon, $color]) {
            $shortcuts[] = ['label' => $label, 'route' => $route, 'icon' => $icon, 'color' => $color];
        }

        $this->render('financeiro/index', [
            'title' => 'Financeiro',
            'stats' => $stats,
            'chart' => $chart,
            'payments' => $payments,
            'shortcuts' => $shortcuts,
        ]);
    }

    public function receber(): void
    {
        [$rows, $paginator, $search, $status] = $this->accounts('receber');
        $extraFilters = $this->statusFilter($status);

        $columns = [
            'Código' => 'codigo',
            'Cliente' => static fn(array $row): string => e($row['parte'] ?? '—'),
            'Descrição' => 'descricao',
            'Origem' => 'origem',
            'Valor' => static fn(array $row): string => e(money($row['valor'] ?? 0)),
            'Pago' => static fn(array $row): string => e(money($row['valor_pago'] ?? 0)),
            'Saldo' => static fn(array $row): string => '<span class="fw-semibold">' . e(money($row['saldo'] ?? 0)) . '</span>',
            'Vencimento' => static fn(array $row): string => e(date_br((string) ($row['vencimento'] ?? ''))),
            'Status' => 'status',
            'Ações' => fn(array $row): string => $this->payAction($row, 'receber'),
        ];

        $this->render('financeiro/receber', [
            'title' => 'Contas a receber',
            'rows' => $rows,
            'columns' => $columns,
            'route' => 'financeiro.receber',
            'createRoute' => can('financeiro', 'create') ? 'financeiro.create-receber' : null,
            'search' => $search,
            'status' => $status,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'actions' => [],
        ]);
    }

    public function pagar(): void
    {
        [$rows, $paginator, $search, $status] = $this->accounts('pagar');
        $extraFilters = $this->statusFilter($status);

        $columns = [
            'Código' => 'codigo',
            'Fornecedor' => static fn(array $row): string => e($row['parte'] ?? '—'),
            'Descrição' => 'descricao',
            'Categoria' => 'origem',
            'Valor' => static fn(array $row): string => e(money($row['valor'] ?? 0)),
            'Pago' => static fn(array $row): string => e(money($row['valor_pago'] ?? 0)),
            'Saldo' => static fn(array $row): string => '<span class="fw-semibold">' . e(money($row['saldo'] ?? 0)) . '</span>',
            'Vencimento' => static fn(array $row): string => e(date_br((string) ($row['vencimento'] ?? ''))),
            'Status' => 'status',
            'Ações' => fn(array $row): string => $this->payAction($row, 'pagar'),
        ];

        $this->render('financeiro/pagar', [
            'title' => 'Contas a pagar',
            'rows' => $rows,
            'columns' => $columns,
            'route' => 'financeiro.pagar',
            'createRoute' => can('financeiro', 'create') ? 'financeiro.create-pagar' : null,
            'search' => $search,
            'status' => $status,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'actions' => [],
        ]);
    }

    public function createReceber(): void
    {
        $defaults = [
            'codigo' => gerar_codigo('CR', 'contas_receber'),
            'cliente_id' => '',
            'origem' => '',
            'referencia_id' => '',
            'descricao' => '',
            'valor' => '0.00',
            'desconto' => '0.00',
            'vencimento' => date('Y-m-d'),
            'status' => 'aberto',
        ];
        $this->renderReceberForm('Nova conta a receber', $this->withOld($defaults), []);
    }

    public function storeReceber(): void
    {
        $data = $this->collectReceber();
        if (($data['codigo'] ?? '') === '') {
            $data['codigo'] = gerar_codigo('CR', 'contas_receber');
        }
        $errors = $this->validateReceber($data);
        if ($errors !== []) {
            set_old($data);
            $this->renderReceberForm('Nova conta a receber', $data, $errors);
            return;
        }

        $valor = (float) $data['valor'];
        $desconto = (float) $data['desconto'];
        $payload = [
            'codigo' => $data['codigo'],
            'cliente_id' => $data['cliente_id'] === '' ? null : (int) $data['cliente_id'],
            'origem' => $data['origem'],
            'referencia_id' => $data['referencia_id'] === '' ? null : (int) $data['referencia_id'],
            'descricao' => $data['descricao'],
            'valor' => number_format($valor, 2, '.', ''),
            'valor_pago' => number_format($data['status'] === 'pago' ? max(0, $valor - $desconto) : 0, 2, '.', ''),
            'desconto' => number_format($desconto, 2, '.', ''),
            'vencimento' => $data['vencimento'],
            'status' => $data['status'],
        ];

        $id = Database::transaction(static fn(): int => Database::insert('contas_receber', $payload));
        log_action('criou_conta_receber', 'financeiro', $id, 'Conta ' . $payload['codigo'] . ' criada.');
        clear_old();
        flash('success', 'Conta a receber cadastrada com sucesso.');
        $this->redirect('financeiro.receber');
    }

    public function createPagar(): void
    {
        $defaults = [
            'codigo' => gerar_codigo('CP', 'contas_pagar'),
            'fornecedor_id' => '',
            'categoria' => '',
            'descricao' => '',
            'valor' => '0.00',
            'vencimento' => date('Y-m-d'),
            'status' => 'aberto',
        ];
        $this->renderPagarForm('Nova conta a pagar', $this->withOld($defaults), []);
    }

    public function storePagar(): void
    {
        $data = $this->collectPagar();
        if (($data['codigo'] ?? '') === '') {
            $data['codigo'] = gerar_codigo('CP', 'contas_pagar');
        }
        $errors = $this->validatePagar($data);
        if ($errors !== []) {
            set_old($data);
            $this->renderPagarForm('Nova conta a pagar', $data, $errors);
            return;
        }

        $valor = (float) $data['valor'];
        $payload = [
            'codigo' => $data['codigo'],
            'fornecedor_id' => $data['fornecedor_id'] === '' ? null : (int) $data['fornecedor_id'],
            'categoria' => $data['categoria'],
            'descricao' => $data['descricao'],
            'valor' => number_format($valor, 2, '.', ''),
            'valor_pago' => number_format($data['status'] === 'pago' ? $valor : 0, 2, '.', ''),
            'vencimento' => $data['vencimento'],
            'status' => $data['status'],
        ];

        $id = Database::transaction(static fn(): int => Database::insert('contas_pagar', $payload));
        log_action('criou_conta_pagar', 'financeiro', $id, 'Conta ' . $payload['codigo'] . ' criada.');
        clear_old();
        flash('success', 'Conta a pagar cadastrada com sucesso.');
        $this->redirect('financeiro.pagar');
    }

    public function pay(): void
    {
        $tipo = (string) Request::input('tipo', '');
        $id = (int) Request::input('id', 0);
        if (!in_array($tipo, ['receber', 'pagar'], true) || $id <= 0) {
            flash('error', 'Informe a conta para registrar o pagamento.');
            $this->redirect('financeiro.index');
        }

        $config = $tipo === 'receber'
            ? ['table' => 'contas_receber', 'route' => 'financeiro.receber', 'label' => 'Conta a receber', 'discount' => true]
            : ['table' => 'contas_pagar', 'route' => 'financeiro.pagar', 'label' => 'Conta a pagar', 'discount' => false];
        $conta = Database::fetch('SELECT * FROM ' . Database::identifier($config['table']) . ' WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($conta === null) {
            abort(404, 'Conta não encontrada.');
        }

        $desconto = $config['discount'] ? (float) ($conta['desconto'] ?? 0) : 0.0;
        $saldo = (float) $conta['valor'] - (float) $conta['valor_pago'] - $desconto;
        $defaults = [
            'valor' => number_format(max(0, $saldo), 2, '.', ''),
            'forma_pagamento' => '',
            'data_pagamento' => date('Y-m-d\TH:i'),
            'observacoes' => '',
        ];

        if (!Request::isPost()) {
            $this->renderPay($conta, $config, $tipo, $saldo, $defaults, []);
            return;
        }

        $data = [
            'valor' => str_replace(',', '.', trim((string) Request::post('valor', ''))),
            'forma_pagamento' => trim((string) Request::post('forma_pagamento', '')),
            'data_pagamento' => $this->normalizeDateTime(trim((string) Request::post('data_pagamento', ''))),
            'observacoes' => trim((string) Request::post('observacoes', '')),
        ];
        $errors = (new Validator([
            'valor' => 'required|numeric',
            'forma_pagamento' => 'required|max:60',
            'data_pagamento' => 'required',
            'observacoes' => 'nullable',
        ]))->validate($data);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $data['data_pagamento']);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if ($parsed === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
            $errors['data_pagamento'] = 'Informe data e hora válidas.';
        }
        if ($errors !== []) {
            set_old($data);
            $values = [];
            foreach ($defaults as $key => $default) {
                $values[$key] = old($key, $default);
            }
            clear_old();
            $this->renderPay($conta, $config, $tipo, $saldo, $values, $errors);
            return;
        }

        $valor = (float) $data['valor'];
        try {
            Database::transaction(function () use ($id, $tipo, $config, $valor, $data): void {
                $row = Database::fetch('SELECT * FROM ' . Database::identifier($config['table']) . ' WHERE id = :id FOR UPDATE', ['id' => $id]);
                if ($row === null) {
                    throw new \RuntimeException('Conta não encontrada.');
                }
                $descontoAtual = $config['discount'] ? (float) ($row['desconto'] ?? 0) : 0.0;
                $saldoAtual = (float) $row['valor'] - (float) $row['valor_pago'] - $descontoAtual;
                if ($valor <= 0) {
                    throw new \RuntimeException('O valor do pagamento deve ser maior que zero.');
                }
                if ($valor > $saldoAtual + 0.001) {
                    throw new \RuntimeException('O valor informado é maior que o saldo em aberto.');
                }
                $valorPago = (float) $row['valor_pago'] + $valor;
                $saldoFinal = (float) $row['valor'] - $valorPago - $descontoAtual;
                Database::insert('pagamentos', [
                    'conta_receber_id' => $tipo === 'receber' ? $id : null,
                    'conta_pagar_id' => $tipo === 'pagar' ? $id : null,
                    'cliente_id' => $tipo === 'receber' ? ($row['cliente_id'] ?? null) : null,
                    'valor' => number_format($valor, 2, '.', ''),
                    'forma_pagamento' => $data['forma_pagamento'],
                    'data_pagamento' => $data['data_pagamento'],
                    'observacoes' => $data['observacoes'] !== '' ? $data['observacoes'] : null,
                    'usuario_id' => $this->userId(),
                ]);
                Database::update($config['table'], [
                    'valor_pago' => number_format($valorPago, 2, '.', ''),
                    'status' => $saldoFinal <= 0.001 ? 'pago' : 'parcial',
                ], 'id = :id', ['id' => $id]);
            });
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect($config['route']);
        }

        log_action('registrou_pagamento', 'financeiro', $id, ucfirst($tipo) . ' de ' . money($valor) . '.');
        clear_old();
        flash('success', 'Pagamento registrado com sucesso.');
        $this->redirect($config['route']);
    }

    public function fluxo(): void
    {
        $defaultDe = date('Y-m-d', strtotime('-29 days'));
        $de = trim((string) Request::get('de', ''));
        $ate = trim((string) Request::get('ate', ''));
        $de = $this->validDate($de) ? $de : $defaultDe;
        $ate = $this->validDate($ate) ? $ate : date('Y-m-d');
        if ($de > $ate) {
            [$de, $ate] = [$ate, $de];
        }

        $params = ['de' => $de, 'ate' => $ate];
        $entradas = [];
        foreach (Database::fetchAll(
            'SELECT DATE(data_pagamento) AS dia, SUM(valor) AS total FROM pagamentos
             WHERE conta_receber_id IS NOT NULL AND DATE(data_pagamento) BETWEEN :de AND :ate GROUP BY dia',
            $params
        ) as $row) {
            $entradas[(string) $row['dia']] = (float) $row['total'];
        }

        $saidas = [];
        $saidasRows = Database::fetchAll(
            'SELECT DATE(data_pagamento) AS dia, SUM(valor) AS total FROM pagamentos
             WHERE conta_pagar_id IS NOT NULL AND DATE(data_pagamento) BETWEEN :de AND :ate GROUP BY dia',
            $params
        );
        $saidasRows = array_merge($saidasRows, Database::fetchAll(
            'SELECT DATE(data_despesa) AS dia, SUM(valor) AS total FROM despesas
             WHERE DATE(data_despesa) BETWEEN :de AND :ate GROUP BY dia',
            $params
        ));
        foreach ($saidasRows as $row) {
            $dia = (string) $row['dia'];
            $saidas[$dia] = ($saidas[$dia] ?? 0) + (float) $row['total'];
        }

        $rows = [];
        $acumulado = 0.0;
        $totalEntradas = 0.0;
        $totalSaidas = 0.0;
        $start = new \DateTimeImmutable($de);
        $end = new \DateTimeImmutable($ate);
        $interval = new \DateInterval('P1D');
        foreach (new \DatePeriod($start, $interval, $end->modify('+1 day')) as $day) {
            $dia = $day->format('Y-m-d');
            $entrada = $entradas[$dia] ?? 0.0;
            $saida = $saidas[$dia] ?? 0.0;
            $acumulado += $entrada - $saida;
            $totalEntradas += $entrada;
            $totalSaidas += $saida;
            $rows[] = [
                'dia' => $dia,
                'entradas' => $entrada,
                'saidas' => $saida,
                'saldo' => $acumulado,
            ];
        }

        $this->render('financeiro/fluxo', [
            'title' => 'Fluxo de caixa',
            'rows' => $rows,
            'de' => $de,
            'ate' => $ate,
            'summary' => [
                'entradas' => $totalEntradas,
                'saidas' => $totalSaidas,
                'saldo' => $acumulado,
            ],
        ]);
    }

    private function accounts(string $tipo): array
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
            if ($tipo === 'receber') {
                $where[] = '(cr.codigo LIKE :q OR c.nome LIKE :q OR cr.descricao LIKE :q OR cr.origem LIKE :q)';
            } else {
                $where[] = '(cp.codigo LIKE :q OR f.nome LIKE :q OR cp.descricao LIKE :q OR cp.categoria LIKE :q)';
            }
            $params['q'] = '%' . $search . '%';
        }
        if ($status !== '') {
            $where[] = ($tipo === 'receber' ? 'cr' : 'cp') . '.status = :status';
            $params['status'] = $status;
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        if ($tipo === 'receber') {
            $from = ' FROM contas_receber cr LEFT JOIN clientes c ON c.id = cr.cliente_id';
            $balance = 'cr.valor - cr.valor_pago - cr.desconto';
            $columns = 'cr.*, c.nome AS parte';
            $alias = 'cr';
        } else {
            $from = ' FROM contas_pagar cp LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id';
            $balance = 'cp.valor - cp.valor_pago';
            $columns = 'cp.*, f.nome AS parte, cp.categoria AS origem';
            $alias = 'cp';
        }

        $total = (int) Database::scalar('SELECT COUNT(*)' . $from . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];
        $rows = Database::fetchAll(
            'SELECT ' . $columns . ', (' . $balance . ') AS saldo' . $from . $sqlWhere . '
             ORDER BY ' . $alias . '.vencimento ASC, ' . $alias . '.id DESC LIMIT :limit OFFSET :offset',
            $params
        );
        return [$rows, $paginator, $search, $status];
    }

    private function renderReceberForm(string $title, array $values, array $errors): void
    {
        $clientes = [];
        foreach (Database::fetchAll('SELECT id, nome FROM clientes ORDER BY nome ASC') as $row) {
            $clientes[(int) $row['id']] = $row['nome'];
        }
        $this->render('financeiro/form-receber', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => 'financeiro.store-receber',
            'cancelRoute' => 'financeiro.index',
            'submitLabel' => 'Cadastrar conta',
            'fields' => [
                'codigo' => ['label' => 'Código', 'type' => 'text', 'rules' => 'required|max:50', 'col' => 'col-md-4', 'help' => 'Gerado automaticamente.'],
                'cliente_id' => ['label' => 'Cliente', 'type' => 'select', 'rules' => 'nullable|integer', 'options' => $clientes, 'col' => 'col-md-8', 'empty' => 'Não informar'],
                'origem' => ['label' => 'Origem', 'type' => 'select', 'rules' => 'required', 'options' => self::ORIGENS, 'col' => 'col-md-6'],
                'referencia_id' => ['label' => 'Referência (ID)', 'type' => 'number', 'rules' => 'nullable|integer', 'col' => 'col-md-6'],
                'descricao' => ['label' => 'Descrição', 'type' => 'text', 'rules' => 'required|max:255', 'col' => 'col-md-12'],
                'valor' => ['label' => 'Valor (R$)', 'type' => 'number', 'rules' => 'required|numeric', 'col' => 'col-md-4'],
                'desconto' => ['label' => 'Desconto (R$)', 'type' => 'number', 'rules' => 'nullable|numeric', 'col' => 'col-md-4'],
                'vencimento' => ['label' => 'Vencimento', 'type' => 'date', 'rules' => 'required|date', 'col' => 'col-md-4'],
                'status' => ['label' => 'Status', 'type' => 'select', 'rules' => 'required', 'options' => self::STATUS, 'col' => 'col-md-4'],
            ],
        ]);
    }

    private function renderPagarForm(string $title, array $values, array $errors): void
    {
        $fornecedores = [];
        foreach (Database::fetchAll('SELECT id, nome FROM fornecedores ORDER BY nome ASC') as $row) {
            $fornecedores[(int) $row['id']] = $row['nome'];
        }
        $this->render('financeiro/form-pagar', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => 'financeiro.store-pagar',
            'cancelRoute' => 'financeiro.index',
            'submitLabel' => 'Cadastrar conta',
            'fields' => [
                'codigo' => ['label' => 'Código', 'type' => 'text', 'rules' => 'required|max:50', 'col' => 'col-md-4', 'help' => 'Gerado automaticamente.'],
                'fornecedor_id' => ['label' => 'Fornecedor', 'type' => 'select', 'rules' => 'nullable|integer', 'options' => $fornecedores, 'col' => 'col-md-8', 'empty' => 'Não informar'],
                'categoria' => ['label' => 'Categoria', 'type' => 'select', 'rules' => 'required', 'options' => self::CATEGORIAS, 'col' => 'col-md-6'],
                'descricao' => ['label' => 'Descrição', 'type' => 'text', 'rules' => 'required|max:255', 'col' => 'col-md-6'],
                'valor' => ['label' => 'Valor (R$)', 'type' => 'number', 'rules' => 'required|numeric', 'col' => 'col-md-4'],
                'vencimento' => ['label' => 'Vencimento', 'type' => 'date', 'rules' => 'required|date', 'col' => 'col-md-4'],
                'status' => ['label' => 'Status', 'type' => 'select', 'rules' => 'required', 'options' => self::STATUS, 'col' => 'col-md-4'],
            ],
        ]);
    }

    private function renderPay(array $conta, array $config, string $tipo, float $saldo, array $values, array $errors): void
    {
        $this->render('financeiro/pay', [
            'title' => 'Registrar ' . ($tipo === 'receber' ? 'recebimento' : 'pagamento'),
            'conta' => $conta,
            'config' => $config,
            'tipo' => $tipo,
            'saldo' => $saldo,
            'values' => $values,
            'errors' => $errors,
            'formasPagamento' => self::FORMAS_PAGAMENTO,
        ]);
    }

    private function collectReceber(): array
    {
        return [
            'codigo' => trim((string) Request::post('codigo', '')),
            'cliente_id' => trim((string) Request::post('cliente_id', '')),
            'origem' => trim((string) Request::post('origem', '')),
            'referencia_id' => trim((string) Request::post('referencia_id', '')),
            'descricao' => trim((string) Request::post('descricao', '')),
            'valor' => str_replace(',', '.', trim((string) Request::post('valor', ''))),
            'desconto' => str_replace(',', '.', trim((string) Request::post('desconto', '0'))),
            'vencimento' => trim((string) Request::post('vencimento', '')),
            'status' => trim((string) Request::post('status', 'aberto')),
        ];
    }

    private function collectPagar(): array
    {
        return [
            'codigo' => trim((string) Request::post('codigo', '')),
            'fornecedor_id' => trim((string) Request::post('fornecedor_id', '')),
            'categoria' => trim((string) Request::post('categoria', '')),
            'descricao' => trim((string) Request::post('descricao', '')),
            'valor' => str_replace(',', '.', trim((string) Request::post('valor', ''))),
            'vencimento' => trim((string) Request::post('vencimento', '')),
            'status' => trim((string) Request::post('status', 'aberto')),
        ];
    }

    private function validateReceber(array $data): array
    {
        $errors = (new Validator([
            'codigo' => 'required|max:50|unique:contas_receber,codigo,id',
            'cliente_id' => 'nullable|integer',
            'origem' => 'required',
            'referencia_id' => 'nullable|integer',
            'descricao' => 'required|max:255',
            'valor' => 'required|numeric',
            'desconto' => 'nullable|numeric',
            'vencimento' => 'required|date',
            'status' => 'required',
        ]))->validate($data + ['id' => 0]);

        if (!array_key_exists($data['origem'], self::ORIGENS)) {
            $errors['origem'] = 'Origem não é permitida.';
        }
        if (!array_key_exists($data['status'], self::STATUS)) {
            $errors['status'] = 'Status não é permitido.';
        }
        if (!isset($errors['valor']) && (float) $data['valor'] <= 0) {
            $errors['valor'] = 'O valor deve ser maior que zero.';
        }
        if (!isset($errors['desconto']) && (float) $data['desconto'] < 0) {
            $errors['desconto'] = 'O desconto não pode ser negativo.';
        }
        if (isset($errors['desconto']) === false && isset($errors['valor']) === false && (float) $data['desconto'] > (float) $data['valor']) {
            $errors['desconto'] = 'O desconto não pode ser maior que o valor.';
        }
        if ($data['cliente_id'] !== '' && Database::scalar('SELECT 1 FROM clientes WHERE id = :id LIMIT 1', ['id' => (int) $data['cliente_id']]) === false) {
            $errors['cliente_id'] = 'Cliente não encontrado.';
        }
        return $errors;
    }

    private function validatePagar(array $data): array
    {
        $errors = (new Validator([
            'codigo' => 'required|max:50|unique:contas_pagar,codigo,id',
            'fornecedor_id' => 'nullable|integer',
            'categoria' => 'required',
            'descricao' => 'required|max:255',
            'valor' => 'required|numeric',
            'vencimento' => 'required|date',
            'status' => 'required',
        ]))->validate($data + ['id' => 0]);

        if (!array_key_exists($data['categoria'], self::CATEGORIAS)) {
            $errors['categoria'] = 'Categoria não é permitida.';
        }
        if (!array_key_exists($data['status'], self::STATUS)) {
            $errors['status'] = 'Status não é permitido.';
        }
        if (!isset($errors['valor']) && (float) $data['valor'] <= 0) {
            $errors['valor'] = 'O valor deve ser maior que zero.';
        }
        if ($data['fornecedor_id'] !== '' && Database::scalar('SELECT 1 FROM fornecedores WHERE id = :id LIMIT 1', ['id' => (int) $data['fornecedor_id']]) === false) {
            $errors['fornecedor_id'] = 'Fornecedor não encontrado.';
        }
        return $errors;
    }

    private function statusFilter(string $status): string
    {
        $html = '<div class="col-md-3 col-lg-2"><label for="status" class="form-label small fw-semibold">Status</label><select name="status" id="status" class="form-select"><option value="">Todos</option>';
        foreach (self::STATUS as $value => $label) {
            $html .= '<option value="' . e($value) . '"' . selected($status, (string) $value) . '>' . e($label) . '</option>';
        }
        return $html . '</select></div>';
    }

    private function payAction(array $row, string $tipo): string
    {
        if (in_array($row['status'] ?? '', ['pago', 'cancelado'], true)) {
            return '<span class="text-muted">—</span>';
        }
        $href = url('financeiro.pay', ['tipo' => $tipo, 'id' => (int) ($row['id'] ?? 0)]);
        $html = '<div class="d-flex justify-content-end">';
        $html .= '<form method="post" action="' . e($href) . '">' . csrf_field();
        $html .= '<input type="hidden" name="tipo" value="' . e($tipo) . '">';
        $html .= '<input type="hidden" name="id" value="' . e((int) ($row['id'] ?? 0)) . '">';
        $html .= '<button type="submit" class="btn btn-sm btn-outline-success" title="' . e($tipo === 'receber' ? 'Receber' : 'Pagar') . '"><i class="fa-solid fa-hand-holding-dollar"></i></button>';
        return $html . '</form></div>';
    }

    private function normalizeDateTime(string $value): string
    {
        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            $value .= ':00';
        }
        return $value;
    }

    private function validDate(string $value): bool
    {
        return $value !== '' && \DateTimeImmutable::createFromFormat('!Y-m-d', $value) !== false;
    }

    private function userId(): int
    {
        return (int) (user()['id'] ?? 0);
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

    private function monthName(int $month): string
    {
        $months = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        return $months[$month] ?? '';
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
