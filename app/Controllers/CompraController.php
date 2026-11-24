<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Validators\Validator;

final class CompraController extends Controller
{
    private const PER_PAGE = 15;

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
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(c.codigo LIKE :q OR f.nome LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $from = ' FROM compras c INNER JOIN fornecedores f ON f.id = c.fornecedor_id';

        $total = (int) Database::scalar('SELECT COUNT(*)' . $from . $sqlWhere, $params);
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];
        $rows = Database::fetchAll(
            'SELECT c.*, f.nome AS fornecedor' . $from . $sqlWhere . ' ORDER BY c.data_compra DESC, c.id DESC LIMIT :limit OFFSET :offset',
            $params
        );

        $columns = [
            'Código' => 'codigo',
            'Data' => static fn(array $row): string => e(date_br((string) ($row['data_compra'] ?? ''))),
            'Fornecedor' => 'fornecedor',
            'Valor total' => static fn(array $row): string => e(money($row['valor_total'] ?? 0)),
            'Status' => 'status',
            'Ações' => static function (array $row): string {
                $id = (int) ($row['id'] ?? 0);
                $html = '<div class="d-flex justify-content-end gap-1 flex-wrap">';
                $html .= render_action('compras.show', ['id' => $id], ['label' => 'Visualizar', 'icon' => 'eye', 'class' => 'btn-outline-primary']);
                if (can('compras', 'delete')) {
                    $html .= render_action('compras.destroy', ['id' => $id], ['label' => 'Excluir', 'icon' => 'trash', 'class' => 'btn-outline-danger', 'method' => 'post', 'confirm' => 'Excluir esta compra? Esta ação não pode ser desfeita.']);
                }
                return $html . '</div>';
            },
        ];

        $this->render('compras/index', [
            'title' => 'Compras',
            'rows' => $rows,
            'columns' => $columns,
            'route' => 'compras',
            'createRoute' => can('compras', 'create') ? 'compras.create' : null,
            'search' => $search,
            'paginator' => $paginator,
            'actions' => [],
        ]);
    }

    public function create(): void
    {
        $defaults = [
            'codigo' => gerar_codigo('CMP', 'compras'),
            'fornecedor_id' => '',
            'data_compra' => date('Y-m-d'),
            'forma_pagamento' => '',
            'itens' => [['produto_id' => '', 'quantidade' => '1', 'valor_unitario' => '0']],
        ];
        $this->renderForm('Nova compra', $this->withOld($defaults), []);
    }

    public function store(): void
    {
        $data = $this->collect();
        if (($data['codigo'] ?? '') === '') {
            $data['codigo'] = gerar_codigo('CMP', 'compras');
        }
        $errors = $this->validate($data);
        if ($errors !== []) {
            set_old($data);
            $this->renderForm('Nova compra', $data, $errors);
            return;
        }

        $valorTotal = 0.0;
        foreach ($data['itens'] as $item) {
            $valorTotal += (int) $item['quantidade'] * (float) $item['valor_unitario'];
        }

        $id = Database::transaction(function () use ($data, $valorTotal): int {
            $compraId = Database::insert('compras', [
                'codigo' => $data['codigo'],
                'fornecedor_id' => (int) $data['fornecedor_id'],
                'usuario_id' => $this->userId(),
                'data_compra' => $data['data_compra'],
                'forma_pagamento' => $data['forma_pagamento'] !== '' ? $data['forma_pagamento'] : null,
                'valor_total' => number_format($valorTotal, 2, '.', ''),
                'status' => 'pendente',
                'recebido_em' => null,
            ]);
            foreach ($data['itens'] as $item) {
                Database::insert('compra_itens', [
                    'compra_id' => $compraId,
                    'produto_id' => (int) $item['produto_id'],
                    'quantidade' => (int) $item['quantidade'],
                    'valor_unitario' => number_format((float) $item['valor_unitario'], 2, '.', ''),
                    'valor_total' => number_format((int) $item['quantidade'] * (float) $item['valor_unitario'], 2, '.', ''),
                ]);
            }
            return $compraId;
        });

        log_action('criou_compra', 'compras', $id, 'Compra ' . $data['codigo'] . ' registrada.');
        clear_old();
        flash('success', 'Compra registrada com sucesso.');
        $this->redirectTo('compras.show', ['id' => $id]);
    }

    public function show(): void
    {
        $id = (int) Request::input('id', 0);
        $compra = Database::fetch(
            'SELECT c.*, f.nome AS fornecedor, u.nome AS usuario
             FROM compras c
             INNER JOIN fornecedores f ON f.id = c.fornecedor_id
             INNER JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.id = :id LIMIT 1',
            ['id' => $id]
        );
        if ($compra === null) {
            abort(404, 'Compra não encontrada.');
        }

        $itens = Database::fetchAll(
            'SELECT ci.*, p.nome AS produto, p.codigo AS codigo, p.lote
             FROM compra_itens ci
             INNER JOIN produtos p ON p.id = ci.produto_id
             WHERE ci.compra_id = :id
             ORDER BY ci.id ASC',
            ['id' => $id]
        );

        $sections = [
            'Compra' => [
                'Código' => $compra['codigo'],
                'Data' => date_br((string) $compra['data_compra']),
                'Fornecedor' => $compra['fornecedor'],
                'Forma de pagamento' => $compra['forma_pagamento'] ?: '—',
                'Valor total' => static fn(): string => e(money($compra['valor_total'] ?? 0)),
                'Status' => static fn(): string => status_badge($compra['status'] ?? null),
                'Recebido em' => date_br($compra['recebido_em'] ?? null, true),
                'Registrado por' => $compra['usuario'],
            ],
        ];

        $this->render('compras/show', [
            'title' => 'Detalhes da compra',
            'compra' => $compra,
            'itens' => $itens,
            'sections' => $sections,
            'actions' => [
                ['label' => 'Voltar', 'route' => 'compras.index', 'icon' => 'arrow-left', 'class' => 'btn-outline-secondary'],
            ],
            'recordId' => $id,
        ]);
    }

    public function receive(): void
    {
        $id = (int) Request::post('id', 0);

        try {
            Database::transaction(function () use ($id): void {
                $compra = Database::fetch('SELECT * FROM compras WHERE id = :id FOR UPDATE', ['id' => $id]);
                if ($compra === null) {
                    throw new \RuntimeException('Compra não encontrada.');
                }
                if ($compra['status'] !== 'pendente') {
                    throw new \RuntimeException('Esta compra não está pendente de recebimento.');
                }

                $itens = Database::fetchAll('SELECT * FROM compra_itens WHERE compra_id = :id ORDER BY id ASC', ['id' => $id]);
                foreach ($itens as $item) {
                    $produto = Database::fetch('SELECT quantidade, lote FROM produtos WHERE id = :id FOR UPDATE', ['id' => (int) $item['produto_id']]);
                    if ($produto === null) {
                        throw new \RuntimeException('Produto da compra não encontrado.');
                    }
                    $anterior = (int) $produto['quantidade'];
                    $quantidade = (int) $item['quantidade'];
                    $atual = $anterior + $quantidade;
                    Database::update('produtos', ['quantidade' => $atual], 'id = :id', ['id' => (int) $item['produto_id']]);
                    Database::insert('estoque_movimentacoes', [
                        'produto_id' => (int) $item['produto_id'],
                        'tipo' => 'entrada',
                        'quantidade' => $quantidade,
                        'quantidade_anterior' => $anterior,
                        'quantidade_atual' => $atual,
                        'lote' => $produto['lote'],
                        'documento' => 'Compra ' . $compra['codigo'],
                        'observacoes' => null,
                        'usuario_id' => $this->userId(),
                    ]);
                }

                Database::update('compras', [
                    'status' => 'recebido',
                    'recebido_em' => date('Y-m-d H:i:s'),
                ], 'id = :id', ['id' => $id]);

                if (strtolower((string) $compra['forma_pagamento']) !== 'dinheiro') {
                    Database::insert('contas_pagar', [
                        'codigo' => gerar_codigo('CP', 'contas_pagar'),
                        'fornecedor_id' => (int) $compra['fornecedor_id'],
                        'categoria' => 'Compras',
                        'descricao' => 'Compra ' . $compra['codigo'],
                        'valor' => number_format((float) $compra['valor_total'], 2, '.', ''),
                        'valor_pago' => 0,
                        'vencimento' => date('Y-m-d', strtotime((string) $compra['data_compra'] . ' +7 days')),
                        'status' => 'aberto',
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirectTo('compras.show', ['id' => $id]);
        }

        log_action('recebeu_compra', 'compras', $id, 'Compra recebida e estoque atualizado.');
        flash('success', 'Compra recebida e estoque atualizado com sucesso.');
        $this->redirectTo('compras.show', ['id' => $id]);
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        $compra = $id > 0 ? Database::fetch('SELECT status, recebido_em FROM compras WHERE id = :id', ['id' => $id]) : null;
        if ($compra === null) {
            abort(404, 'Compra não encontrada.');
        }

        if (($compra['status'] ?? '') === 'recebido' || ($compra['recebido_em'] ?? null) !== null) {
            flash('error', 'Não é possível excluir: esta compra já foi recebida.');
            $this->redirect('compras.index');
        }

        Database::transaction(static fn(): int => Database::delete('compras', 'id = :id', ['id' => $id]));
        log_action('excluiu_compra', 'compras', $id, 'Compra excluída.');
        clear_old();
        flash('success', 'Compra excluída com sucesso.');
        $this->redirect('compras.index');
    }

    private function renderForm(string $title, array $values, array $errors): void
    {
        $produtos = [];
        foreach (Database::fetchAll('SELECT id, codigo, nome FROM produtos ORDER BY nome ASC') as $row) {
            $produtos[(int) $row['id']] = $row['nome'] . ' · ' . $row['codigo'];
        }
        $fornecedores = [];
        foreach (Database::fetchAll('SELECT id, nome FROM fornecedores ORDER BY nome ASC') as $row) {
            $fornecedores[(int) $row['id']] = $row['nome'];
        }

        $this->render('compras/form', [
            'title' => $title,
            'values' => $values,
            'errors' => $errors,
            'action' => 'compras.store',
            'cancelRoute' => 'compras.index',
            'submitLabel' => 'Registrar compra',
            'produtos' => $produtos,
            'fornecedores' => $fornecedores,
            'formasPagamento' => self::FORMAS_PAGAMENTO,
        ]);
    }

    private function collect(): array
    {
        $data = [
            'codigo' => trim((string) Request::post('codigo', '')),
            'fornecedor_id' => trim((string) Request::post('fornecedor_id', '')),
            'data_compra' => trim((string) Request::post('data_compra', '')),
            'forma_pagamento' => trim((string) Request::post('forma_pagamento', '')),
            'itens' => [],
        ];
        $rawItens = Request::post('itens', []);
        if (!is_array($rawItens)) {
            $rawItens = [];
        }
        foreach ($rawItens as $item) {
            if (!is_array($item)) {
                continue;
            }
            $data['itens'][] = [
                'produto_id' => trim((string) ($item['produto_id'] ?? '')),
                'quantidade' => trim((string) ($item['quantidade'] ?? '')),
                'valor_unitario' => str_replace(',', '.', trim((string) ($item['valor_unitario'] ?? ''))),
            ];
        }
        return $data;
    }

    private function validate(array $data): array
    {
        $errors = (new Validator([
            'codigo' => 'required|max:50|unique:compras,codigo,id',
            'fornecedor_id' => 'required|integer',
            'data_compra' => 'required|date',
            'forma_pagamento' => 'nullable|max:60',
        ]))->validate($data + ['id' => 0]);

        if (!isset($errors['fornecedor_id']) && Database::scalar('SELECT 1 FROM fornecedores WHERE id = :id LIMIT 1', ['id' => (int) $data['fornecedor_id']]) === false) {
            $errors['fornecedor_id'] = 'Fornecedor não encontrado.';
        }
        if ($data['itens'] === []) {
            $errors['itens'] = 'Adicione ao menos um item à compra.';
            return $errors;
        }

        foreach ($data['itens'] as $index => $item) {
            if ($item['produto_id'] === '' || filter_var($item['produto_id'], FILTER_VALIDATE_INT) === false) {
                $errors["itens.{$index}.produto_id"] = 'Selecione um produto.';
            } elseif (Database::scalar('SELECT 1 FROM produtos WHERE id = :id LIMIT 1', ['id' => (int) $item['produto_id']]) === false) {
                $errors["itens.{$index}.produto_id"] = 'Produto não encontrado.';
            }
            if (filter_var($item['quantidade'], FILTER_VALIDATE_INT) === false || (int) $item['quantidade'] < 1) {
                $errors["itens.{$index}.quantidade"] = 'Informe uma quantidade maior que zero.';
            }
            if (!is_numeric($item['valor_unitario']) || (float) $item['valor_unitario'] < 0) {
                $errors["itens.{$index}.valor_unitario"] = 'Informe um valor válido.';
            }
        }
        return $errors;
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

    private function redirectTo(string $route, array $params = []): never
    {
        if (!headers_sent()) {
            header('Location: ' . url($route, $params), true, 302);
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
