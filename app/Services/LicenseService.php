<?php

declare(strict_types=1);

namespace PetCare\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use PetCare\Core\Database;
use PetCare\Core\Session;

final class LicenseService
{
    private static ?array $localClinic = null;
    private static ?array $localLicense = null;

    public static function flush(): void
    {
        self::$localClinic = null;
        self::$localLicense = null;
    }

    public static function localClinic(): array
    {
        if (self::$localClinic !== null) {
            return self::$localClinic;
        }

        $row = Database::fetch(
            'SELECT id, codigo, nome, responsavel, cnpj, email, telefone, local_instalacao, ativo
             FROM clinicas
             ORDER BY local_instalacao DESC, id ASC
             LIMIT 1'
        );
        if ($row === null) {
            throw new InvalidArgumentException('Nenhuma clínica local foi configurada para o licenciamento.');
        }

        self::$localClinic = $row;
        return $row;
    }

    public static function localLicense(): ?array
    {
        $clinic = self::localClinic();
        $id = (int) $clinic['id'];
        if (self::$localLicense !== null && (int) self::$localLicense['clinica_id'] === $id) {
            return self::$localLicense;
        }

        self::$localLicense = self::findByClinic($id);
        return self::$localLicense;
    }

    public static function findByClinic(int $clinicId): ?array
    {
        return Database::fetch(
            'SELECT l.*, c.codigo AS clinica_codigo, c.nome AS clinica_nome, c.responsavel AS clinica_responsavel,
                    c.local_instalacao, c.ativo AS clinica_ativa
             FROM licencas l
             INNER JOIN clinicas c ON c.id = l.clinica_id
             WHERE l.clinica_id = :id LIMIT 1',
            ['id' => $clinicId]
        );
    }

    public static function status(): array
    {
        $clinic = self::localClinic();
        $license = self::localLicense();
        $hoje = new DateTimeImmutable('today');

        if ($license === null) {
            return [
                'allowed' => false,
                'permanente' => false,
                'status' => 'sem_licenca',
                'status_label' => 'Sem licença',
                'vencimento' => null,
                'ativacao' => null,
                'dias_restantes' => null,
                'modalidade' => '—',
                'clinica' => $clinic,
                'license' => null,
            ];
        }

        $permanent = (int) ($license['licenca_permanente'] ?? 0) === 1 || ($license['status'] ?? '') === 'permanente';
        if ($permanent) {
            return [
                'allowed' => true,
                'permanente' => true,
                'status' => 'permanente',
                'status_label' => 'Ilimitada',
                'vencimento' => null,
                'ativacao' => $license['data_ativacao'] ?? null,
                'dias_restantes' => null,
                'modalidade' => self::modalityLabel((string) ($license['modalidade'] ?? '')),
                'clinica' => $clinic,
                'license' => $license,
            ];
        }

        $vencimentoRaw = (string) ($license['data_vencimento'] ?? '');
        $vencimento = $vencimentoRaw !== '' ? new DateTimeImmutable($vencimentoRaw . ' 23:59:59') : null;

        if ($vencimento !== null && $vencimento < $hoje) {
            if (($license['status'] ?? '') !== 'expirada') {
                self::syncExpired();
                self::flush();
                $license = self::localLicense() ?? $license;
            }
            return [
                'allowed' => false,
                'permanente' => false,
                'status' => 'expirada',
                'status_label' => 'Expirada',
                'vencimento' => $license['data_vencimento'] ?? $vencimentoRaw,
                'ativacao' => $license['data_ativacao'] ?? null,
                'dias_restantes' => 0,
                'modalidade' => self::modalityLabel((string) ($license['modalidade'] ?? '')),
                'clinica' => $clinic,
                'license' => $license,
            ];
        }

        $allowed = $vencimento !== null && in_array((string) ($license['status'] ?? ''), ['ativa', 'prorrogada'], true);
        $diasRestantes = $vencimento !== null ? max(0, (int) $vencimento->diff($hoje)->days) : null;
        $status = $allowed ? 'ativa' : 'inativa';

        return [
            'allowed' => $allowed,
            'permanente' => false,
            'status' => $status,
            'status_label' => $allowed ? 'Ativa' : 'Inativa',
            'vencimento' => $license['data_vencimento'] ?? null,
            'ativacao' => $license['data_ativacao'] ?? null,
            'dias_restantes' => $diasRestantes,
            'modalidade' => self::modalityLabel((string) ($license['modalidade'] ?? '')),
            'clinica' => $clinic,
            'license' => $license,
        ];
    }

    public static function allowed(): bool
    {
        return (bool) self::status()['allowed'];
    }

    public static function warning(): ?array
    {
        $status = self::status();
        if (!$status['allowed'] || $status['permanente']) {
            return null;
        }

        $dias = (int) ($status['dias_restantes'] ?? -1);
        if (!in_array($dias, self::settings()['avisos'], true)) {
            return null;
        }

        $message = $dias === 0
            ? 'A licença do PetCare expira hoje. Procure o proprietário do sistema para prorrogar.'
            : 'A licença do PetCare vence em ' . $dias . ' ' . ($dias === 1 ? 'dia' : 'dias') . '. Procure o proprietário do sistema para prorrogar.';

        return [
            'dias' => $dias,
            'message' => $message,
            'level' => $dias <= 1 ? 'danger' : 'warning',
        ];
    }

    public static function settings(): array
    {
        $dias = (int) Database::scalar("SELECT valor FROM configuracoes WHERE chave = 'licenca_dias_iniciais' LIMIT 1");
        $dias = $dias > 0 ? min($dias, 3650) : 30;

        $raw = (string) Database::scalar("SELECT valor FROM configuracoes WHERE chave = 'licenca_avisos' LIMIT 1");
        $avisos = self::parseAvisos($raw);

        return ['dias_iniciais' => $dias, 'avisos' => $avisos, 'avisos_raw' => implode(',', $avisos)];
    }

    public static function saveSettings(int $diasIniciais, string $avisos): void
    {
        $diasIniciais = max(1, min(3650, $diasIniciais));
        $parsed = self::parseAvisos($avisos);

        self::upsertSetting('licenca_dias_iniciais', (string) $diasIniciais);
        self::upsertSetting('licenca_avisos', implode(',', $parsed));
    }

    public static function blockDate(): ?string
    {
        $license = self::localLicense();
        if ($license === null || self::isPermanent($license)) {
            return null;
        }

        $raw = (string) ($license['data_vencimento'] ?? '');
        if ($raw === '') {
            return null;
        }

        return (new DateTimeImmutable($raw . ' 23:59:59'))->modify('+1 day')->format('Y-m-d');
    }

    public static function setBlockDate(int $clinicId, string $data): array
    {
        $license = self::ensureLicense($clinicId);
        if (self::isPermanent($license)) {
            throw new InvalidArgumentException('Desative a licença ilimitada antes de definir uma data de bloqueio.');
        }

        $data = trim($data);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        if ($parsed === false || $parsed->format('Y-m-d') !== $data) {
            throw new InvalidArgumentException('Informe uma data de bloqueio válida.');
        }

        $hoje = new DateTimeImmutable('today');
        if ($parsed < $hoje) {
            throw new InvalidArgumentException('A data do bloqueio não pode estar no passado.');
        }

        $vencimento = $parsed->modify('-1 day')->format('Y-m-d');
        $anterior = (string) ($license['data_vencimento'] ?? '');

        Database::transaction(static function () use ($license, $data, $hoje, $vencimento, $anterior): void {
            Database::update('licencas', [
                'modalidade' => 'data_bloqueio',
                'data_ativacao' => (string) ($license['data_ativacao'] ?? '') !== '' ? $license['data_ativacao'] : $hoje->format('Y-m-d'),
                'data_vencimento' => $vencimento,
                'status' => 'ativa',
                'licenca_permanente' => 0,
            ], 'id = :id', ['id' => (int) $license['id']]);
            self::record(
                (int) $license['id'],
                'data_bloqueio',
                null,
                $anterior !== '' ? $anterior : null,
                $vencimento,
                'Data do bloqueio definida para ' . $data . '.'
            );
        });

        self::flush();
        return self::findByClinic($clinicId) ?? [];
    }

    public static function all(): array
    {
        self::syncExpired();

        $rows = Database::fetchAll(
            'SELECT l.*, c.codigo AS clinica_codigo, c.nome AS clinica_nome, c.responsavel AS clinica_responsavel,
                    c.cnpj AS clinica_cnpj, c.email AS clinica_email, c.local_instalacao, c.ativo AS clinica_ativa
             FROM licencas l
             INNER JOIN clinicas c ON c.id = l.clinica_id
             ORDER BY c.local_instalacao DESC, c.nome ASC, l.id ASC'
        );

        $hoje = new DateTimeImmutable('today');
        foreach ($rows as &$row) {
            $permanent = (int) ($row['licenca_permanente'] ?? 0) === 1 || ($row['status'] ?? '') === 'permanente';
            $vencimentoRaw = (string) ($row['data_vencimento'] ?? '');
            $vencimento = $vencimentoRaw !== '' ? new DateTimeImmutable($vencimentoRaw . ' 23:59:59') : null;
            $expirada = $vencimento !== null && $vencimento < $hoje;
            $bloqueada = !$permanent && ($expirada || $vencimento === null || !in_array((string) ($row['status'] ?? ''), ['ativa', 'prorrogada'], true));

            $row['permanente'] = $permanent;
            $row['expirada'] = $expirada;
            $row['bloqueada'] = $bloqueada;
            $row['dias_restantes'] = !$permanent && $vencimento !== null ? max(0, (int) $vencimento->diff($hoje)->days) : null;
            $row['status_efetivo'] = $permanent ? 'permanente' : ($bloqueada ? ($expirada ? 'expirada' : 'inativa') : 'ativa');
            $row['modalidade_label'] = self::modalityLabel((string) ($row['modalidade'] ?? ''));
        }
        unset($row);

        return $rows;
    }

    public static function history(?int $licenseId = null): array
    {
        $sql = 'SELECT h.*, u.nome AS usuario_nome, l.codigo_licenca, c.nome AS clinica_nome
                FROM historico_licencas h
                INNER JOIN licencas l ON l.id = h.licenca_id
                INNER JOIN clinicas c ON c.id = l.clinica_id
                LEFT JOIN usuarios u ON u.id = h.usuario_responsavel';
        $params = [];
        if ($licenseId !== null && $licenseId > 0) {
            $sql .= ' WHERE h.licenca_id = :licenca_id';
            $params['licenca_id'] = $licenseId;
        }
        $sql .= ' ORDER BY h.created_at DESC, h.id DESC';

        return Database::fetchAll($sql, $params);
    }

    public static function activate(int $clinicId): array
    {
        $license = self::ensureLicense($clinicId);
        $dias = self::settings()['dias_iniciais'];
        $hoje = new DateTimeImmutable('today');
        $vencimento = $hoje->modify('+' . $dias . ' days');
        $anterior = (string) ($license['data_vencimento'] ?? '');

        Database::transaction(static function () use ($license, $dias, $hoje, $vencimento, $anterior): void {
            Database::update('licencas', [
                'modalidade' => '30_dias',
                'dias_contratados' => $dias,
                'data_ativacao' => $hoje->format('Y-m-d'),
                'data_vencimento' => $vencimento->format('Y-m-d'),
                'status' => 'ativa',
                'licenca_permanente' => 0,
            ], 'id = :id', ['id' => (int) $license['id']]);
            self::record(
                (int) $license['id'],
                'ativacao_30_dias',
                $dias,
                $anterior !== '' ? $anterior : null,
                $vencimento->format('Y-m-d'),
                'Licença inicial ativada por ' . $dias . ' dias.'
            );
        });

        self::flush();
        return self::findByClinic($clinicId) ?? [];
    }

    public static function extend(int $clinicId, int $dias): array
    {
        $license = self::ensureLicense($clinicId);
        $dias = max(1, min(3650, $dias));
        $hoje = new DateTimeImmutable('today');
        $anterior = (string) ($license['data_vencimento'] ?? '');
        $permanent = (int) ($license['licenca_permanente'] ?? 0) === 1;
        $base = $anterior !== '' && !$permanent && new DateTimeImmutable($anterior . ' 23:59:59') >= $hoje
            ? new DateTimeImmutable($anterior . ' 23:59:59')
            : $hoje;
        $novo = $base->modify('+' . $dias . ' days');
        $reativacao = ($license['status'] ?? '') === 'expirada' || $anterior === '' || $permanent;

        Database::transaction(static function () use ($license, $dias, $hoje, $novo, $anterior, $reativacao): void {
            Database::update('licencas', [
                'modalidade' => 'prorrogada',
                'dias_contratados' => (int) ($license['dias_contratados'] ?? 0) + $dias,
                'data_ativacao' => $reativacao ? $hoje->format('Y-m-d') : ($license['data_ativacao'] ?? null),
                'data_vencimento' => $novo->format('Y-m-d'),
                'status' => 'ativa',
                'licenca_permanente' => 0,
            ], 'id = :id', ['id' => (int) $license['id']]);
            self::record(
                (int) $license['id'],
                $reativacao ? 'reativacao' : 'prorrogacao',
                $dias,
                $anterior !== '' ? $anterior : null,
                $novo->format('Y-m-d'),
                ($reativacao ? 'Licença reativada' : 'Licença prorrogada') . ' por ' . $dias . ' dias.'
            );
        });

        self::flush();
        return self::findByClinic($clinicId) ?? [];
    }

    public static function setPermanent(int $clinicId, bool $permanent, int $dias = 0): array
    {
        $license = self::ensureLicense($clinicId);
        $hoje = new DateTimeImmutable('today');
        $anterior = (string) ($license['data_vencimento'] ?? '');

        if ($permanent) {
            Database::transaction(static function () use ($license, $anterior): void {
                Database::update('licencas', [
                    'modalidade' => 'permanente',
                    'data_vencimento' => null,
                    'status' => 'permanente',
                    'licenca_permanente' => 1,
                ], 'id = :id', ['id' => (int) $license['id']]);
                self::record(
                    (int) $license['id'],
                    'licenca_ilimitada',
                    null,
                    $anterior !== '' ? $anterior : null,
                    null,
                    'Licença ilimitada ativada; o bloqueio por vencimento foi desativado.'
                );
            });
        } else {
            $dias = max(1, min(3650, $dias));
            $novo = $hoje->modify('+' . $dias . ' days');
            Database::transaction(static function () use ($license, $dias, $hoje, $novo, $anterior): void {
                Database::update('licencas', [
                    'modalidade' => 'prorrogada',
                    'dias_contratados' => (int) ($license['dias_contratados'] ?? 0) + $dias,
                    'data_ativacao' => $hoje->format('Y-m-d'),
                    'data_vencimento' => $novo->format('Y-m-d'),
                    'status' => 'ativa',
                    'licenca_permanente' => 0,
                ], 'id = :id', ['id' => (int) $license['id']]);
                self::record(
                    (int) $license['id'],
                    'desativacao_ilimitada',
                    $dias,
                    null,
                    $novo->format('Y-m-d'),
                    'Licença ilimitada desativada; prazo de ' . $dias . ' dias definido.'
                );
            });
        }

        self::flush();
        return self::findByClinic($clinicId) ?? [];
    }

    public static function syncExpired(): void
    {
        Database::update(
            'licencas',
            ['status' => 'expirada'],
            "status IN ('ativa','prorrogada') AND data_vencimento IS NOT NULL AND data_vencimento < :hoje",
            ['hoje' => (new DateTimeImmutable('today'))->format('Y-m-d')]
        );
    }

    private static function isPermanent(array $license): bool
    {
        return (int) ($license['licenca_permanente'] ?? 0) === 1 || ($license['status'] ?? '') === 'permanente';
    }

    private static function ensureLicense(int $clinicId): array
    {
        $clinic = Database::fetch('SELECT id, nome FROM clinicas WHERE id = :id AND ativo = 1 LIMIT 1', ['id' => $clinicId]);
        if ($clinic === null) {
            throw new InvalidArgumentException('Clínica não encontrada.');
        }

        $license = self::findByClinic($clinicId);
        if ($license !== null) {
            return $license;
        }

        $id = Database::insert('licencas', [
            'clinica_id' => $clinicId,
            'codigo_licenca' => sprintf('LIC-%s-%05d', date('Y'), $clinicId),
            'modalidade' => '30_dias',
            'dias_contratados' => self::settings()['dias_iniciais'],
            'status' => 'inativa',
            'licenca_permanente' => 0,
        ]);

        return Database::fetch('SELECT * FROM licencas WHERE id = :id LIMIT 1', ['id' => $id]) ?? [];
    }

    private static function record(
        int $licenseId,
        string $tipo,
        ?int $dias,
        ?string $anterior,
        ?string $novo,
        string $descricao
    ): void {
        Database::insert('historico_licencas', [
            'licenca_id' => $licenseId,
            'usuario_responsavel' => (int) Session::get('user_id', 0) ?: null,
            'tipo_operacao' => $tipo,
            'dias_adicionados' => $dias,
            'vencimento_anterior' => $anterior,
            'novo_vencimento' => $novo,
            'descricao' => $descricao,
            'ip_responsavel' => function_exists('request_ip') ? \request_ip() : null,
        ]);
        if (function_exists('log_action')) {
            \log_action('licenca_' . $tipo, 'licenciamento', $licenseId, $descricao);
        }
    }

    private static function upsertSetting(string $key, string $value): void
    {
        $exists = (int) Database::scalar('SELECT COUNT(*) FROM configuracoes WHERE chave = :chave', ['chave' => $key]);
        if ($exists > 0) {
            Database::update('configuracoes', ['valor' => $value], 'chave = :chave', ['chave' => $key]);
            return;
        }
        Database::insert('configuracoes', ['chave' => $key, 'valor' => $value]);
    }

    private static function parseAvisos(string $raw): array
    {
        $values = array_map('intval', explode(',', $raw));
        $values = array_values(array_unique(array_filter($values, static fn(int $value): bool => $value >= 0 && $value <= 365)));
        sort($values, SORT_DESC);
        return $values === [] ? [10, 5, 3, 1, 0] : $values;
    }

    private static function modalityLabel(string $modality): string
    {
        return match ($modality) {
            '30_dias' => '30 dias',
            'prorrogada' => 'Prorrogada',
            'data_bloqueio' => 'Data do bloqueio',
            'permanente' => 'Ilimitada',
            default => ucfirst(str_replace('_', ' ', $modality)),
        };
    }
}
