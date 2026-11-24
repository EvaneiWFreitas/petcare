<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class EstoqueController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $situacao = (string) Request::get('situacao', 'todos');
        if (!in_array($situacao, ['todos', 'baixo', 'vencendo', 'vencido'], true)) {
            $situacao = 'todos';
        }

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(p.nome LIKE :q OR p.codigo LIKE :q OR p.lote LIKE :q OR p.codigo_barras LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($situacao === 'baixo') {
            $where[] = 'p.quantidade <= p.estoque_minimo';
        } elseif ($situacao === 'vencendo') {
            $where[] = 'p.data_validade IS NOT NULL AND p.data_validade >= CURDATE() AND p.data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        } elseif ($situacao === 'vencido') {
            $where[] = 'p.data_validade IS NOT NULL AND p.data_validade < CURDATE()';
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $from = ' FROM produtos p LEFT JOIN categorias cat ON cat.id = p.categoria_id';

        $total = (int) Database::scalar('SELECT COUNT(*)' . $from . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];
        $rows = Database::fetchAll(
            'SELECT p.*, cat.nome AS categoria' . $from . $sqlWhere . ' ORDER BY p.nome ASC, p.id ASC LIMIT :limit OFFSET :offset',
            $params
        );

        $summary = [
            'total' => (int) Database::scalar('SELECT COUNT(*) FROM produtos'),
            'baixo' => (int) Database::scalar('SELECT COUNT(*) FROM produtos WHERE quantidade <= estoque_minimo'),
            'vencendo' => (int) Database::scalar('SELECT COUNT(*) FROM produtos WHERE data_validade >= CURDATE() AND data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)'),
            'vencido' => (int) Database::scalar('SELECT COUNT(*) FROM produtos WHERE data_validade < CURDATE()'),
            'valor_total' => (float) Database::scalar('SELECT COALESCE(SUM(quantidade * preco_venda), 0) FROM produtos'),
        ];

        $extraFilters = '<div class="col-md-3 col-lg-2"><label for="situacao" class="form-label small fw-semibold">Situação</label><select name="situacao" id="situacao" class="form-select">';
        foreach (['todos' => 'Todos', 'baixo' => 'Estoque baixo', 'vencendo' => 'Vencendo em 30 dias', 'vencido' => 'Vencidos'] as $value => $label) {
            $extraFilters .= '<option value="' . e($value) . '"' . selected($situacao, (string) $value) . '>' . e($label) . '</option>';
        }
        $extraFilters .= '</select></div>';

        $columns = [
            'Código' => 'codigo',
            'Produto' => static fn(array $row): string => e($row['nome'] ?? '') . '<div class="small text-muted">' . e($row['fabricante'] ?? '') . '</div>',
            'Categoria' => static fn(array $row): string => e($row['categoria'] ?? '—'),
            'Lote' => 'lote',
            'Quantidade' => fn(array $row): string => $this->quantityBadge($row),
            'Valor venda' => static fn(array $row): string => e(money($row['preco_venda'] ?? 0)),
            'Validade' => fn(array $row): string => $this->expiryBadge($row['data_validade'] ?? null),
            'Ações' => static function (array $row): string {
                $id = (int) ($row['id'] ?? 0);
                $html = '<div class="d-flex justify-content-end gap-1 flex-wrap">';
                $html .= render_action('estoque.edit', ['id' => $id], ['label' => 'Editar', 'icon' => 'pen', 'class' => 'btn-outline-secondary']);
                $html .= render_action('estoque.movements', ['produto_id' => $id], ['label' => 'Movimentar', 'icon' => 'arrow-right-arrow-left', 'class' => 'btn-outline-primary']);
                if (can('farmacia', 'delete')) {
                    $html .= render_action('estoque.destroy', ['id' => $id], ['label' => 'Excluir', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Excluir este produto? Esta ação não pode ser desfeita.']);
                }
                return $html . '</div>';
            },
        ];

        $this->render('estoque/index', [
            'title' => 'Estoque / Farmácia',
            'rows' => $rows,
            'columns' => $columns,
            'route' => 'estoque',
            'createRoute' => can('farmacia', 'create') ? 'estoque.create' : null,
            'search' => $search,
            'situacao' => $situacao,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'summary' => $summary,
            'actions' => [],
        ]);
    }

    public function create(): void
    {
        $this->renderForm('Novo produto', $this->withOld($this->defaults()), []);
    }

    public function store(): void
    {
        $data = $this->collect();
        if (($data['codigo'] ?? '') === '') {
            $data['codigo'] = gerar_codigo('PRD', 'produtos');
        }
        $errors = $this->validate($data, 0);
        if ($errors !== []) {
            set_old($data);
            $this->renderForm('Novo produto', $data, $errors);
            return;
        }

        $payload = $this->payload($data);
        $id = Database::transaction(function () use ($payload): int {
            $productId = Database::insert('produtos', $payload);
            if ((int) $payload['quantidade'] > 0) {
                Database::insert('estoque_movimentacoes', [
                    'produto_id' => $productId,
                    'tipo' => 'entrada',
                    'quantidade' => (int) $payload['quantidade'],
                    'quantidade_anterior' => 0,
                    'quantidade_atual' => (int) $payload['quantidade'],
                    'lote' => $payload['lote'],
                    'documento' => 'estoque inicial',
                    'observacoes' => null,
                    'usuario_id' => $this->userId(),
                ]);
            }
            return $productId;
        });

        log_action('criou_produto', 'farmacia', $id, 'Produto ' . $data['nome'] . ' cadastrado.');
        clear_old();
        flash('success', 'Produto cadastrado com sucesso.');
        $this->redirect('estoque.index');
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) Request::input('id', 0);
        $produto = Database::fetch('SELECT * FROM produtos WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($produto === null) {
            abort(404, 'Produto não encontrado.');
        }
        $this->renderForm('Editar produto', $this->withOld($produto + ['id' => $id]), [], $id);
    }

    public function update(): void
    {
        $id = (int) Request::post('id', 0);
        $produto = Database::fetch('SELECT * FROM produtos WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($produto === null) {
            abort(404, 'Produto não encontrado.');
        }

        $data = $this->collect();
        $errors = $this->validate($data, $id);
        if ($errors !== []) {
            set_old($data + ['id' => $id]);
            $this->renderForm('Editar produto', $data + ['id' => $id], $errors, $id);
            return;
        }

        $payload = $this->payload($data);
        $anterior = (int) $produto['quantidade'];
        $atual = (int) $payload['quantidade'];

        Database::transaction(function () use ($payload, $id, $produto, $anterior, $atual): void {
            Database::update('produtos', $payload, 'id = :id', ['id' => $id]);
            if ($anterior !== $atual) {
                Database::insert('estoque_movimentacoes', [
                    'produto_id' => $id,
                    'tipo' => $atual > $anterior ? 'entrada' : 'saida',
                    'quantidade' => abs($atual - $anterior),
                    'quantidade_anterior' => $anterior,
                    'quantidade_atual' => $atual,
                    'lote' => $payload['lote'] ?? ($produto['lote'] ?? null),
                    'documento' => 'ajuste de estoque',
                    'observacoes' => null,
                    'usuario_id' => $this->userId(),
                ]);
            }
        });

        log_action('editou_produto', 'farmacia', $id, 'Produto ' . $data['nome'] . ' atualizado.');
        clear_old();
        flash('success', 'Produto atualizado com sucesso.');
        $this->redirect('estoque.index');
    }

    public function movements(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $produtoId = (int) Request::get('produto_id', 0);
        $tipo = (string) Request::get('tipo', '');
        if (!in_array($tipo, ['entrada', 'saida'], true)) {
            $tipo = '';
        }
        $de = trim((string) Request::get('de', ''));
        $ate = trim((string) Request::get('ate', ''));

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(p.nome LIKE :q OR p.codigo LIKE :q OR m.documento LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($produtoId > 0) {
            $where[] = 'm.produto_id = :produto_id';
            $params['produto_id'] = $produtoId;
        }
        if ($tipo !== '') {
            $where[] = 'm.tipo = :tipo';
            $params['tipo'] = $tipo;
        }
        if ($this->validDate($de)) {
            $where[] = 'DATE(m.created_at) >= :de';
            $params['de'] = $de;
        }
        if ($this->validDate($ate)) {
            $where[] = 'DATE(m.created_at) <= :ate';
            $params['ate'] = $ate;
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $from = ' FROM estoque_movimentacoes m
                  INNER JOIN produtos p ON p.id = m.produto_id
                  LEFT JOIN usuarios u ON u.id = m.usuario_id';

        $total = (int) Database::scalar('SELECT COUNT(*)' . $from . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];
        $rows = Database::fetchAll(
            'SELECT m.*, p.nome AS produto, p.codigo AS codigo, u.nome AS usuario' . $from . $sqlWhere . '
             ORDER BY m.created_at DESC, m.id DESC LIMIT :limit OFFSET :offset',
            $params
        );

        $produtos = Database::fetchAll('SELECT id, codigo, nome FROM produtos ORDER BY nome ASC');
        $extraFilters = '<div class="col-md-3"><label for="produto_id" class="form-label small fw-semibold">Produto</label><select name="produto_id" id="produto_id" class="form-select"><option value="">Todos</option>';
        foreach ($produtos as $produto) {
            $extraFilters .= '<option value="' . e($produto['id']) . '"' . selected($produtoId, (int) $produto['id']) . '>' . e($produto['nome'] . ' · ' . $produto['codigo']) . '</option>';
        }
        $extraFilters .= '</select></div>';
        $extraFilters .= '<div class="col-md-2"><label for="tipo" class="form-label small fw-semibold">Tipo</label><select name="tipo" id="tipo" class="form-select"><option value="">Todos</option>';
        foreach (['entrada' => 'Entrada', 'saida' => 'Saída'] as $value => $label) {
            $extraFilters .= '<option value="' . e($value) . '"' . selected($tipo, (string) $value) . '>' . e($label) . '</option>';
        }
        $extraFilters .= '</select></div>';
        $extraFilters .= '<div class="col-md-2"><label for="de" class="form-label small fw-semibold">De</label><input type="date" name="de" id="de" class="form-control" value="' . e($de) . '"></div>';
        $extraFilters .= '<div class="col-md-2"><label for="ate" class="form-label small fw-semibold">Até</label><input type="date" name="ate" id="ate" class="form-control" value="' . e($ate) . '"></div>';

        $columns = [
            'Data' => static fn(array $row): string => e(date_br((string) ($row['created_at'] ?? ''), true)),
            'Produto' => static fn(array $row): string => e($row['produto'] ?? '') . '<div class="small text-muted">' . e($row['codigo'] ?? '') . '</div>',
            'Tipo' => fn(array $row): string => $this->movementBadge($row['tipo'] ?? null),
            'Quantidade' => static fn(array $row): string => '<span class="fw-semibold">' . e((int) ($row['quantidade'] ?? 0)) . '</span>',
            'Anterior' => static fn(array $row): string => e((int) ($row['quantidade_anterior'] ?? 0)),
            'Atual' => static fn(array $row): string => e((int) ($row['quantidade_atual'] ?? 0)),
            'Documento' => 'documento',
            'Usuário' => static fn(array $row): string => e($row['usuario'] ?? '—'),
        ];

        $this->render('estoque/movements', [
            'title' => 'Movimentações de estoque',
            'rows' => $rows,
            'columns' => $columns,
            'route' => 'estoque',
            'createRoute' => null,
            'search' => $search,
            'extraFilters' => $extraFilters,
            'paginator' => $paginator,
            'actions' => [],
            'produtos' => $produtos,
            'produtoId' => $produtoId,
            'tipo' => $tipo,
            'de' => $de,
            'ate' => $ate,
        ]);
    }

    public function move(): void
    {
        $produtoId = (int) Request::post('produto_id', 0);
        $tipo = (string) Request::post('tipo', '');
        $quantidade = (int) Request::post('quantidade', 0);
        $lote = trim((string) Request::post('lote', ''));
        $documento = trim((string) Request::post('documento', ''));
        $observacoes = trim((string) Request::post('observacoes', ''));

        if ($produtoId <= 0 || !in_array($tipo, ['entrada', 'saida'], true) || $quantidade <= 0) {
            flash('error', 'Informe produto, tipo e quantidade válidos para a movimentação.');
            $this->redirect('estoque.movements');
        }

        try {
            Database::transaction(function () use ($produtoId, $tipo, $quantidade, $lote, $documento, $observacoes): void {
                $produto = Database::fetch('SELECT quantidade, lote FROM produtos WHERE id = :id FOR UPDATE', ['id' => $produtoId]);
                if ($produto === null) {
                    throw new \RuntimeException('Produto não encontrado.');
                }
                $anterior = (int) $produto['quantidade'];
                if ($tipo === 'saida' && $quantidade > $anterior) {
                    throw new \RuntimeException('Quantidade insuficiente em estoque.');
                }
                $atual = $tipo === 'entrada' ? $anterior + $quantidade : $anterior - $quantidade;
                Database::update('produtos', ['quantidade' => $atual], 'id = :id', ['id' => $produtoId]);
                Database::insert('estoque_movimentacoes', [
                    'produto_id' => $produtoId,
                    'tipo' => $tipo,
                    'quantidade' => $quantidade,
                    'quantidade_anterior' => $anterior,
                    'quantidade_atual' => $atual,
                    'lote' => $lote !== '' ? $lote : ($produto['lote'] ?? null),
                    'documento' => $documento !== '' ? $documento : null,
                    'observacoes' => $observacoes !== '' ? $observacoes : null,
                    'usuario_id' => $this->userId(),
                ]);
            });
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('estoque.movements');
        }

        log_action('moveu_estoque', 'farmacia', $produtoId, ucfirst($tipo) . ' de ' . $quantidade . ' unidade(s).');
        flash('success', 'Movimentação registrada com sucesso.');
        $this->redirect('estoque.movements');
    }

    private function renderForm(string $title, array $values, array $errors, ?int $id = null): void
    {
        $this->render('estoque/form', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => $id === null ? 'estoque.store' : 'estoque.update',
            'cancelRoute' => 'estoque.index',
            'submitLabel' => $id === null ? 'Cadastrar produto' : 'Salvar alterações',
            'fields' => $this->fields(),
        ]);
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        if ($id <= 0 || (int) Database::scalar('SELECT COUNT(*) FROM produtos WHERE id = :id', ['id' => $id]) === 0) {
            abort(404, 'Produto não encontrado.');
        }

        $vinculos = (int) Database::scalar('SELECT COUNT(*) FROM estoque_movimentacoes WHERE produto_id = :id', ['id' => $id])
            + (int) Database::scalar('SELECT COUNT(*) FROM compra_itens WHERE produto_id = :id', ['id' => $id]);
        if ($vinculos > 0) {
            flash('error', 'Não é possível excluir: o produto possui movimentações ou compras vinculadas.');
            $this->redirect('estoque.index');
        }

        Database::transaction(static fn(): int => Database::delete('produtos', 'id = :id', ['id' => $id]));
        log_action('excluiu_produto', 'farmacia', $id, 'Produto excluído.');
        clear_old();
        flash('success', 'Produto excluído com sucesso.');
        $this->redirect('estoque.index');
    }

    private function fields(): array
    {
        $categorias = [];
        foreach (Database::fetchAll('SELECT id, nome FROM categorias ORDER BY nome ASC') as $row) {
            $categorias[(int) $row['id']] = $row['nome'];
        }
        $fornecedores = [];
        foreach (Database::fetchAll('SELECT id, nome FROM fornecedores ORDER BY nome ASC') as $row) {
            $fornecedores[(int) $row['id']] = $row['nome'];
        }

        return [
            'id' => ['label' => 'ID', 'type' => 'text', 'col' => 'd-none'],
            'codigo' => ['label' => 'Código', 'type' => 'text', 'rules' => 'required|max:50', 'col' => 'col-md-4', 'help' => 'Gerado automaticamente.'],
            'nome' => ['label' => 'Nome do produto', 'type' => 'text', 'rules' => 'required|max:180', 'col' => 'col-md-8'],
            'categoria_id' => ['label' => 'Categoria', 'type' => 'select', 'rules' => 'nullable|integer', 'options' => $categorias, 'col' => 'col-md-4', 'empty' => 'Sem categoria'],
            'fornecedor_id' => ['label' => 'Fornecedor', 'type' => 'select', 'rules' => 'nullable|integer', 'options' => $fornecedores, 'col' => 'col-md-4', 'empty' => 'Sem fornecedor'],
            'fabricante' => ['label' => 'Fabricante', 'type' => 'text', 'rules' => 'nullable|max:150', 'col' => 'col-md-4'],
            'lote' => ['label' => 'Lote', 'type' => 'text', 'rules' => 'nullable|max:80', 'col' => 'col-md-4'],
            'codigo_barras' => ['label' => 'Código de barras', 'type' => 'text', 'rules' => 'nullable|max:80', 'col' => 'col-md-4'],
            'quantidade' => ['label' => 'Quantidade', 'type' => 'number', 'rules' => 'required|integer', 'col' => 'col-md-4'],
            'estoque_minimo' => ['label' => 'Estoque mínimo', 'type' => 'number', 'rules' => 'required|integer', 'col' => 'col-md-4'],
            'preco_custo' => ['label' => 'Preço de custo (R$)', 'type' => 'number', 'rules' => 'required|numeric', 'col' => 'col-md-4'],
            'preco_venda' => ['label' => 'Preço de venda (R$)', 'type' => 'number', 'rules' => 'required|numeric', 'col' => 'col-md-4'],
            'data_fabricacao' => ['label' => 'Data de fabricação', 'type' => 'date', 'rules' => 'nullable|date', 'col' => 'col-md-4'],
            'data_validade' => ['label' => 'Data de validade', 'type' => 'date', 'rules' => 'nullable|date', 'col' => 'col-md-4'],
        ];
    }

    private function defaults(): array
    {
        return [
            'id' => 0,
            'codigo' => gerar_codigo('PRO', 'produtos'),
            'nome' => '',
            'categoria_id' => '',
            'fornecedor_id' => '',
            'fabricante' => '',
            'lote' => '',
            'codigo_barras' => '',
            'quantidade' => 0,
            'estoque_minimo' => 0,
            'preco_custo' => '0.00',
            'preco_venda' => '0.00',
            'data_fabricacao' => '',
            'data_validade' => '',
        ];
    }

    private function collect(): array
    {
        $data = [];
        foreach (['codigo', 'nome', 'fabricante', 'lote', 'codigo_barras', 'data_fabricacao', 'data_validade'] as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : '';
        }
        foreach (['categoria_id', 'fornecedor_id'] as $field) {
            $value = trim((string) Request::post($field, ''));
            $data[$field] = $value === '' ? null : (int) $value;
        }
        foreach (['quantidade', 'estoque_minimo'] as $field) {
            $data[$field] = trim((string) Request::post($field, '0'));
        }
        foreach (['preco_custo', 'preco_venda'] as $field) {
            $value = trim((string) Request::post($field, '0'));
            $data[$field] = $value === '' ? '0' : str_replace(',', '.', $value);
        }
        return $data;
    }

    private function validate(array $data, int $id): array
    {
        $errors = (new Validator([
            'codigo' => 'required|max:50|unique:produtos,codigo,id',
            'nome' => 'required|max:180',
            'categoria_id' => 'nullable|integer',
            'fornecedor_id' => 'nullable|integer',
            'fabricante' => 'nullable|max:150',
            'lote' => 'nullable|max:80',
            'codigo_barras' => 'nullable|max:80|unique:produtos,codigo_barras,id',
            'quantidade' => 'required|integer',
            'estoque_minimo' => 'required|integer',
            'preco_custo' => 'required|numeric',
            'preco_venda' => 'required|numeric',
            'data_fabricacao' => 'nullable|date',
            'data_validade' => 'nullable|date',
        ]))->validate($data + ['id' => $id]);

        if (isset($errors['quantidade']) === false && (int) $data['quantidade'] < 0) {
            $errors['quantidade'] = 'A quantidade não pode ser negativa.';
        }
        if (isset($errors['estoque_minimo']) === false && (int) $data['estoque_minimo'] < 0) {
            $errors['estoque_minimo'] = 'O estoque mínimo não pode ser negativo.';
        }
        foreach (['preco_custo', 'preco_venda'] as $field) {
            if (isset($errors[$field]) === false && (float) $data[$field] < 0) {
                $errors[$field] = 'O valor não pode ser negativo.';
            }
        }
        if ($data['data_fabricacao'] !== '' && $data['data_validade'] !== '' && $data['data_validade'] < $data['data_fabricacao']) {
            $errors['data_validade'] = 'A validade não pode ser anterior à fabricação.';
        }
        foreach (['categoria_id' => 'categorias', 'fornecedor_id' => 'fornecedores'] as $field => $table) {
            if (!isset($errors[$field]) && $data[$field] !== null) {
                $exists = Database::scalar('SELECT 1 FROM ' . Database::identifier($table) . ' WHERE id = :id LIMIT 1', ['id' => $data[$field]]);
                if ($exists === false) {
                    $errors[$field] = 'Registro não encontrado.';
                }
            }
        }
        return $errors;
    }

    private function payload(array $data): array
    {
        return [
            'codigo' => $data['codigo'],
            'nome' => $data['nome'],
            'categoria_id' => $data['categoria_id'],
            'fornecedor_id' => $data['fornecedor_id'],
            'fabricante' => $data['fabricante'] !== '' ? $data['fabricante'] : null,
            'lote' => $data['lote'] !== '' ? $data['lote'] : null,
            'codigo_barras' => $data['codigo_barras'] !== '' ? $data['codigo_barras'] : null,
            'quantidade' => (int) $data['quantidade'],
            'estoque_minimo' => (int) $data['estoque_minimo'],
            'preco_custo' => number_format((float) $data['preco_custo'], 2, '.', ''),
            'preco_venda' => number_format((float) $data['preco_venda'], 2, '.', ''),
            'data_fabricacao' => $data['data_fabricacao'] !== '' ? $data['data_fabricacao'] : null,
            'data_validade' => $data['data_validade'] !== '' ? $data['data_validade'] : null,
        ];
    }

    private function quantityBadge(array $row): string
    {
        $quantidade = (int) ($row['quantidade'] ?? 0);
        $minimo = (int) ($row['estoque_minimo'] ?? 0);
        $class = $quantidade <= $minimo ? 'danger' : 'success';
        return '<span class="badge bg-' . e($class) . ' rounded-pill">' . e($quantidade) . '</span>';
    }

    private function expiryBadge(?string $date): string
    {
        if ($date === null || $date === '') {
            return '<span class="text-muted">—</span>';
        }
        if ($date < date('Y-m-d')) {
            $class = 'danger';
            $label = 'Vencido';
        } elseif ($date <= date('Y-m-d', strtotime('+30 days'))) {
            $class = 'warning text-dark';
            $label = 'Vencendo';
        } else {
            $class = 'success';
            $label = 'Em dia';
        }
        return '<span class="badge bg-' . e($class) . ' rounded-pill">' . e(date_br($date)) . ' · ' . e($label) . '</span>';
    }

    private function movementBadge(?string $tipo): string
    {
        $label = $tipo === 'entrada' ? 'Entrada' : ($tipo === 'saida' ? 'Saída' : '—');
        $class = $tipo === 'entrada' ? 'success' : ($tipo === 'saida' ? 'danger' : 'secondary');
        return '<span class="badge bg-' . e($class) . ' rounded-pill">' . e($label) . '</span>';
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
