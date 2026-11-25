<?php

declare(strict_types=1);

namespace PetCare\Services;

use PetCare\Core\Database;

final class AtendimentoService
{
    public static function syncFinalizados(): int
    {
        $rows = Database::fetchAll(
            'SELECT ag.id
             FROM agendamentos ag
             LEFT JOIN atendimentos at ON at.agendamento_id = ag.id
             WHERE ag.status = :status AND at.id IS NULL
             ORDER BY ag.data_agendamento ASC, ag.id ASC',
            ['status' => 'finalizado']
        );

        $total = 0;
        foreach ($rows as $row) {
            if (self::createFromAgendamento((int) $row['id']) !== null) {
                ++$total;
            }
        }

        return $total;
    }

    public static function createFromAgendamento(int $agendamentoId): ?int
    {
        if ($agendamentoId <= 0) {
            return null;
        }

        $agendamento = Database::fetch(
            'SELECT ag.id, ag.codigo, ag.cliente_id, ag.animal_id, ag.veterinario_id, ag.data_agendamento,
                    ag.hora_inicio, ag.valor, ag.observacoes, ag.status
             FROM agendamentos ag
             WHERE ag.id = :id LIMIT 1',
            ['id' => $agendamentoId]
        );
        if ($agendamento === null || ($agendamento['status'] ?? '') !== 'finalizado') {
            return null;
        }
        if (Database::scalar('SELECT 1 FROM atendimentos WHERE agendamento_id = :id LIMIT 1', ['id' => $agendamentoId]) !== false) {
            return null;
        }

        $data = date('Y-m-d H:i:s');
        $id = Database::insert('atendimentos', [
            'codigo' => self::codigo(),
            'agendamento_id' => $agendamentoId,
            'animal_id' => (int) $agendamento['animal_id'],
            'veterinario_id' => (int) $agendamento['veterinario_id'],
            'data_atendimento' => $data,
            'queixa_principal' => (string) ($agendamento['observacoes'] ?? '') !== '' ? $agendamento['observacoes'] : null,
            'status' => 'finalizado',
            'valor' => number_format((float) $agendamento['valor'], 2, '.', ''),
        ]);

        if (function_exists('log_action')) {
            \log_action('importou_atendimento_finalizado', 'atendimentos', $id, 'Atendimento criado automaticamente a partir do agendamento ' . $agendamento['codigo'] . '.');
        }

        return $id;
    }

    private static function codigo(): string
    {
        do {
            $codigo = \gerar_codigo('ATD', 'atendimentos');
        } while (Database::scalar('SELECT 1 FROM atendimentos WHERE codigo = :codigo LIMIT 1', ['codigo' => $codigo]) !== false);

        return $codigo;
    }
}
