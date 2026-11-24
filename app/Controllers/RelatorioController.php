<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;

final class RelatorioController extends Controller
{
    private const TYPES = [
        'clientes' => 'Clientes',
        'animais' => 'Animais',
        'atendimentos' => 'Atendimentos',
        'consultas_veterinario' => 'Consultas por veterinário',
        'vacinas_aplicadas' => 'Vacinas aplicadas',
        'vacinas_pendentes' => 'Vacinas pendentes',
        'exames' => 'Exames',
        'cirurgias' => 'Cirurgias',
        'internacoes' => 'Internações',
        'produtos_vendidos' => 'Produtos vendidos',
        'estoque' => 'Estoque',
        'produtos_vencidos' => 'Produtos vencidos',
        'contas_pagar' => 'Contas a pagar',
        'contas_receber' => 'Contas a receber',
        'faturamento' => 'Faturamento',
        'despesas' => 'Despesas',
        'lucro' => 'Lucro',
    ];

    public function index(): void
    {
        $veterinarios = Database::fetchAll('SELECT id, nome FROM veterinarios WHERE ativo = 1 ORDER BY nome ASC');
        $this->render('relatorios/index', [
            'title' => 'Relatórios',
            'types' => self::TYPES,
            'veterinarios' => $veterinarios,
            'inicio' => date('Y-m-01'),
            'fim' => date('Y-m-t'),
        ]);
    }

    public function generate(): void
    {
        $tipo = trim((string) Request::get('tipo', ''));
        if (!array_key_exists($tipo, self::TYPES)) {
            flash('error', 'Tipo de relatório inválido.');
            $this->redirect('relatorios.index');
        }
        $inicio = $this->sanitizeDate((string) Request::get('inicio', date('Y-m-01')), date('Y-m-01'));
        $fim = $this->sanitizeDate((string) Request::get('fim', date('Y-m-t')), date('Y-m-t'));
        if ($inicio > $fim) {
            [$inicio, $fim] = [$fim, $inicio];
        }
        $formato = (string) Request::get('formato', 'tela');
        if (!in_array($formato, ['tela', 'csv', 'impressao'], true)) {
            $formato = 'tela';
        }
        $veterinarioId = (int) Request::get('veterinario_id', 0);

        $report = $this->buildReport($tipo, $inicio, $fim, $veterinarioId);
        $report['title'] = self::TYPES[$tipo];
        $report['tipo'] = $tipo;
        $report['periodo'] = ['inicio' => $inicio, 'fim' => $fim];
        $report['formato'] = $formato;
        $report['veterinario_id'] = $veterinarioId;

        if ($formato === 'csv') {
            $this->exportCsv($tipo, $report);
        }
        if ($formato === 'impressao') {
            $this->render('relatorios/result', $report, 'print');
            return;
        }
        $this->render('relatorios/result', $report);
    }

    private function buildReport(string $tipo, string $inicio, string $fim, int $veterinarioId): array
    {
        $params = ['inicio' => $inicio . ' 00:00:00', 'fim' => $fim . ' 23:59:59', 'd_inicio' => $inicio, 'd_fim' => $fim];
        $vetFilter = '';
        if ($veterinarioId > 0) {
            $params['veterinario_id'] = $veterinarioId;
        }

        $columns = [];
        $rows = [];
        $valueColumn = null;

        switch ($tipo) {
            case 'clientes':
                $columns = ['Código' => 'codigo', 'Nome' => 'nome', 'CPF' => 'cpf', 'Telefone' => 'telefone', 'E-mail' => 'email', 'Cadastro' => 'cadastro'];
                $rows = Database::fetchAll(
                    'SELECT codigo, nome, cpf, telefone, email, DATE_FORMAT(created_at, "%d/%m/%Y") AS cadastro
                     FROM clientes WHERE created_at BETWEEN :inicio AND :fim ORDER BY created_at DESC',
                    ['inicio' => $params['inicio'], 'fim' => $params['fim']]
                );
                break;
            case 'animais':
                $columns = ['Nome' => 'nome', 'Tutor' => 'tutor', 'Espécie' => 'especie', 'Raça' => 'raca', 'Cadastro' => 'cadastro'];
                $rows = Database::fetchAll(
                    'SELECT a.nome, c.nome AS tutor, e.nome AS especie, r.nome AS raca, DATE_FORMAT(a.created_at, "%d/%m/%Y") AS cadastro
                     FROM animais a
                     INNER JOIN clientes c ON c.id = a.cliente_id
                     LEFT JOIN especies e ON e.id = a.especie_id
                     LEFT JOIN racas r ON r.id = a.raca_id
                     WHERE a.created_at BETWEEN :inicio AND :fim
                     ORDER BY a.created_at DESC',
                    ['inicio' => $params['inicio'], 'fim' => $params['fim']]
                );
                break;
            case 'atendimentos':
                if ($veterinarioId > 0) {
                    $vetFilter = ' AND at.veterinario_id = :veterinario_id';
                }
                $columns = ['Data' => 'data', 'Pet' => 'pet', 'Tutor' => 'tutor', 'Veterinário' => 'veterinario', 'Status' => 'status'];
                $sql = 'SELECT DATE_FORMAT(at.data_atendimento, "%d/%m/%Y %H:%i") AS data, a.nome AS pet, c.nome AS tutor, v.nome AS veterinario, at.status
                        FROM atendimentos at
                        INNER JOIN animais a ON a.id = at.animal_id
                        INNER JOIN clientes c ON c.id = at.cliente_id
                        INNER JOIN veterinarios v ON v.id = at.veterinario_id
                        WHERE at.data_atendimento BETWEEN :inicio AND :fim' . $vetFilter . '
                        ORDER BY at.data_atendimento DESC';
                $rows = Database::fetchAll($sql, $this->onlyParams($params, $vetFilter !== ''));
                break;
            case 'consultas_veterinario':
                $columns = ['Veterinário' => 'veterinario', 'Consultas' => 'total', 'Valor' => 'valor'];
                $rows = Database::fetchAll(
                    'SELECT v.nome AS veterinario, COUNT(*) AS total, COALESCE(SUM(ag.valor),0) AS valor
                     FROM agendamentos ag
                     INNER JOIN veterinarios v ON v.id = ag.veterinario_id
                     WHERE ag.data_agendamento BETWEEN :d_inicio AND :d_fim
                     GROUP BY v.id, v.nome
                     ORDER BY total DESC',
                    ['d_inicio' => $inicio, 'd_fim' => $fim]
                );
                $valueColumn = 'valor';
                break;
            case 'vacinas_aplicadas':
                $columns = ['Data' => 'data', 'Pet' => 'pet', 'Tutor' => 'tutor', 'Vacina' => 'vacina', 'Veterinário' => 'veterinario'];
                $rows = Database::fetchAll(
                    'SELECT DATE_FORMAT(vc.data_aplicacao, "%d/%m/%Y") AS data, a.nome AS pet, c.nome AS tutor, va.nome AS vacina, COALESCE(v.nome, "—") AS veterinario
                     FROM vacinacoes vc
                     INNER JOIN animais a ON a.id = vc.animal_id
                     INNER JOIN clientes c ON c.id = vc.cliente_id
                     INNER JOIN vacinas va ON va.id = vc.vacina_id
                     LEFT JOIN veterinarios v ON v.id = vc.veterinario_id
                     WHERE vc.data_aplicacao BETWEEN :d_inicio AND :d_fim
                     ORDER BY vc.data_aplicacao DESC',
                    ['d_inicio' => $inicio, 'd_fim' => $fim]
                );
                break;
            case 'vacinas_pendentes':
                $columns = ['Próxima dose' => 'data', 'Pet' => 'pet', 'Tutor' => 'tutor', 'Vacina' => 'vacina', 'Situação' => 'situacao'];
                $rows = Database::fetchAll(
                    'SELECT DATE_FORMAT(vc.proxima_dose, "%d/%m/%Y") AS data, a.nome AS pet, c.nome AS tutor, va.nome AS vacina,
                            CASE WHEN vc.proxima_dose < CURDATE() THEN "Atrasada" ELSE "Pendente" END AS situacao
                     FROM vacinacoes vc
                     INNER JOIN animais a ON a.id = vc.animal_id
                     INNER JOIN clientes c ON c.id = vc.cliente_id
                     INNER JOIN vacinas va ON va.id = vc.vacina_id
                     WHERE vc.proxima_dose IS NOT NULL AND vc.proxima_dose BETWEEN :d_inicio AND :d_fim
                     ORDER BY vc.proxima_dose ASC',
                    ['d_inicio' => $inicio, 'd_fim' => $fim]
                );
                break;
            case 'exames':
                $columns = ['Código' => 'codigo', 'Solicitação' => 'data', 'Pet' => 'pet', 'Tipo' => 'tipo', 'Status' => 'status'];
                $rows = Database::fetchAll(
                    'SELECT e.codigo, DATE_FORMAT(e.data_solicitacao, "%d/%m/%Y") AS data, a.nome AS pet, e.tipo, e.status
                     FROM exames e
                     INNER JOIN animais a ON a.id = e.animal_id
                     WHERE e.data_solicitacao BETWEEN :d_inicio AND :d_fim
                     ORDER BY e.data_solicitacao DESC',
                    ['d_inicio' => $inicio, 'd_fim' => $fim]
                );
                break;
            case 'cirurgias':
                $columns = ['Código' => 'codigo', 'Data' => 'data', 'Pet' => 'pet', 'Procedimento' => 'procedimento', 'Status' => 'status'];
                $rows = Database::fetchAll(
                    'SELECT c.codigo, DATE_FORMAT(c.data_cirurgia, "%d/%m/%Y %H:%i") AS data, a.nome AS pet, c.procedimento, c.status
                     FROM cirurgias c
                     INNER JOIN animais a ON a.id = c.animal_id
                     WHERE c.data_cirurgia BETWEEN :inicio AND :fim
                     ORDER BY c.data_cirurgia DESC',
                    ['inicio' => $params['inicio'], 'fim' => $params['fim']]
                );
                break;
            case 'internacoes':
                $columns = ['Código' => 'codigo', 'Entrada' => 'entrada', 'Alta' => 'alta', 'Pet' => 'pet', 'Status' => 'status'];
                $rows = Database::fetchAll(
                    'SELECT i.codigo, DATE_FORMAT(i.data_entrada, "%d/%m/%Y %H:%i") AS entrada,
                            DATE_FORMAT(i.data_alta, "%d/%m/%Y %H:%i") AS alta, a.nome AS pet, i.status
                     FROM internacoes i
                     INNER JOIN animais a ON a.id = i.animal_id
                     WHERE i.data_entrada BETWEEN :inicio AND :fim
                     ORDER BY i.data_entrada DESC',
                    ['inicio' => $params['inicio'], 'fim' => $params['fim']]
                );
                break;
            case 'produtos_vendidos':
                $columns = ['Data' => 'data', 'Produto' => 'produto', 'Quantidade' => 'quantidade', 'Valor' => 'valor', 'Documento' => 'documento'];
                $rows = Database::fetchAll(
                    'SELECT DATE_FORMAT(m.created_at, "%d/%m/%Y %H:%i") AS data, p.nome AS produto, m.quantidade,
                            (m.quantidade * p.preco_venda) AS valor, COALESCE(m.documento, "") AS documento
                     FROM estoque_movimentacoes m
                     INNER JOIN produtos p ON p.id = m.produto_id
                     WHERE m.tipo = "saida" AND m.documento LIKE "%venda%" AND m.created_at BETWEEN :inicio AND :fim
                     ORDER BY m.created_at DESC',
                    ['inicio' => $params['inicio'], 'fim' => $params['fim']]
                );
                $valueColumn = 'valor';
                break;
            case 'estoque':
                $columns = ['Código' => 'codigo', 'Produto' => 'nome', 'Quantidade' => 'quantidade', 'Mínimo' => 'estoque_minimo', 'Validade' => 'validade', 'Valor venda' => 'valor'];
                $rows = Database::fetchAll(
                    'SELECT codigo, nome, quantidade, estoque_minimo, DATE_FORMAT(data_validade, "%d/%m/%Y") AS validade, preco_venda AS valor
                     FROM produtos ORDER BY nome ASC'
                );
                $valueColumn = 'valor';
                break;
            case 'produtos_vencidos':
                $columns = ['Código' => 'codigo', 'Produto' => 'nome', 'Validade' => 'validade', 'Quantidade' => 'quantidade', 'Valor' => 'valor'];
                $rows = Database::fetchAll(
                    'SELECT codigo, nome, DATE_FORMAT(data_validade, "%d/%m/%Y") AS validade, quantidade, (quantidade * preco_custo) AS valor
                     FROM produtos
                     WHERE data_validade IS NOT NULL AND data_validade < CURDATE()
                     ORDER BY data_validade ASC'
                );
                $valueColumn = 'valor';
                break;
            case 'contas_pagar':
                $columns = ['Código' => 'codigo', 'Descrição' => 'descricao', 'Vencimento' => 'vencimento', 'Status' => 'status', 'Valor' => 'valor'];
                $rows = Database::fetchAll(
                    'SELECT codigo, descricao, DATE_FORMAT(vencimento, "%d/%m/%Y") AS vencimento, status, valor
                     FROM contas_pagar
                     WHERE vencimento BETWEEN :d_inicio AND :d_fim
                     ORDER BY vencimento ASC',
                    ['d_inicio' => $inicio, 'd_fim' => $fim]
                );
                $valueColumn = 'valor';
                break;
            case 'contas_receber':
                $columns = ['Código' => 'codigo', 'Descrição' => 'descricao', 'Vencimento' => 'vencimento', 'Status' => 'status', 'Valor' => 'valor'];
                $rows = Database::fetchAll(
                    'SELECT codigo, descricao, DATE_FORMAT(vencimento, "%d/%m/%Y") AS vencimento, status, valor
                     FROM contas_receber
                     WHERE vencimento BETWEEN :d_inicio AND :d_fim
                     ORDER BY vencimento ASC',
                    ['d_inicio' => $inicio, 'd_fim' => $fim]
                );
                $valueColumn = 'valor';
                break;
            case 'faturamento':
                $columns = ['Data' => 'data', 'Origem' => 'origem', 'Forma' => 'forma_pagamento', 'Valor' => 'valor'];
                $rows = Database::fetchAll(
                    'SELECT DATE_FORMAT(p.data_pagamento, "%d/%m/%Y %H:%i") AS data,
                            CASE WHEN p.conta_receber_id IS NOT NULL THEN "Conta a receber" ELSE "Outro" END AS origem,
                            p.forma_pagamento, p.valor
                     FROM pagamentos p
                     WHERE p.conta_receber_id IS NOT NULL AND p.data_pagamento BETWEEN :inicio AND :fim
                     ORDER BY p.data_pagamento DESC',
                    ['inicio' => $params['inicio'], 'fim' => $params['fim']]
                );
                $valueColumn = 'valor';
                break;
            case 'despesas':
                $columns = ['Data' => 'data', 'Descrição' => 'descricao', 'Categoria' => 'categoria', 'Valor' => 'valor'];
                $despesas = Database::fetchAll(
                    'SELECT DATE_FORMAT(data_despesa, "%d/%m/%Y") AS data, descricao, COALESCE(categoria, "") AS categoria, valor
                     FROM despesas WHERE data_despesa BETWEEN :d_inicio AND :d_fim',
                    ['d_inicio' => $inicio, 'd_fim' => $fim]
                );
                $pagamentos = Database::fetchAll(
                    'SELECT DATE_FORMAT(p.data_pagamento, "%d/%m/%Y") AS data,
                            COALESCE(cp.descricao, "Pagamento conta a pagar") AS descricao,
                            COALESCE(cp.categoria, "Contas a pagar") AS categoria,
                            p.valor
                     FROM pagamentos p
                     LEFT JOIN contas_pagar cp ON cp.id = p.conta_pagar_id
                     WHERE p.conta_pagar_id IS NOT NULL AND p.data_pagamento BETWEEN :inicio AND :fim',
                    ['inicio' => $params['inicio'], 'fim' => $params['fim']]
                );
                $rows = array_merge($despesas, $pagamentos);
                usort($rows, static fn(array $a, array $b): int => strcmp((string) ($b['data'] ?? ''), (string) ($a['data'] ?? '')));
                $valueColumn = 'valor';
                break;
            case 'lucro':
                $receitas = (float) Database::scalar(
                    'SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE conta_receber_id IS NOT NULL AND data_pagamento BETWEEN :inicio AND :fim',
                    ['inicio' => $params['inicio'], 'fim' => $params['fim']]
                );
                $despesasPagas = (float) Database::scalar(
                    'SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE conta_pagar_id IS NOT NULL AND data_pagamento BETWEEN :inicio AND :fim',
                    ['inicio' => $params['inicio'], 'fim' => $params['fim']]
                );
                $despesasDiretas = (float) Database::scalar(
                    'SELECT COALESCE(SUM(valor),0) FROM despesas WHERE data_despesa BETWEEN :d_inicio AND :d_fim',
                    ['d_inicio' => $inicio, 'd_fim' => $fim]
                );
                $totalDespesas = $despesasPagas + $despesasDiretas;
                $lucro = $receitas - $totalDespesas;
                $columns = ['Indicador' => 'indicador', 'Valor' => 'valor'];
                $rows = [
                    ['indicador' => 'Receitas', 'valor' => $receitas],
                    ['indicador' => 'Despesas (contas a pagar)', 'valor' => $despesasPagas],
                    ['indicador' => 'Despesas (diretas)', 'valor' => $despesasDiretas],
                    ['indicador' => 'Lucro', 'valor' => $lucro],
                ];
                $valueColumn = 'valor';
                break;
        }

        $totais = [];
        if ($valueColumn !== null) {
            $sum = 0.0;
            foreach ($rows as $row) {
                $sum += (float) ($row[$valueColumn] ?? 0);
            }
            $totais[$valueColumn] = $sum;
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'totais' => $totais,
            'valueColumn' => $valueColumn,
        ];
    }

    private function exportCsv(string $tipo, array $report): void
    {
        $filename = 'petcare_relatorio_' . preg_replace('/[^a-z0-9_]/', '', $tipo) . '_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        $out = fopen('php://output', 'wb');
        if ($out === false) {
            exit;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_keys($report['columns']), ';');
        foreach ($report['rows'] as $row) {
            $line = [];
            foreach ($report['columns'] as $key) {
                $value = $row[$key] ?? '';
                if (($report['valueColumn'] ?? null) === $key && is_numeric($value)) {
                    $value = number_format((float) $value, 2, ',', '.');
                }
                $line[] = $value;
            }
            fputcsv($out, $line, ';');
        }
        fclose($out);
        exit;
    }

    private function sanitizeDate(string $date, string $default): string
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dt === false || $dt->format('Y-m-d') !== $date) {
            return $default;
        }
        return $date;
    }

    private function onlyParams(array $params, bool $hasVet): array
    {
        $out = [
            'inicio' => $params['inicio'],
            'fim' => $params['fim'],
        ];
        if ($hasVet) {
            $out['veterinario_id'] = $params['veterinario_id'];
        }
        return $out;
    }
}
