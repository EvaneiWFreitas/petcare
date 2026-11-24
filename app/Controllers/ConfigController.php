<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Services\UploadService;

final class ConfigController extends Controller
{
    private const KEYS = [
        'clinica_nome', 'slogan', 'logo', 'cnpj', 'endereco', 'telefone', 'whatsapp', 'email', 'horario_funcionamento',
        'dados_emitente', 'crmv_padrao', 'resolucao_sanitaria',
        'moeda', 'fuso_horario', 'retencao_dados_anos', 'backup_automatico',
        'email_integracao', 'whatsapp_integracao',
        'cabecalho_impressao', 'rodape_impressao',
    ];

    public function index(): void
    {
        $settings = $this->allSettings();
        $this->render('config/index', [
            'title' => 'Configurações',
            'settings' => $settings,
        ]);
    }

    public function update(): void
    {
        $currentLogo = (string) ($this->allSettings()['logo'] ?? '');
        $removeLogo = Request::has('remover_logo');
        $data = [];
        foreach (self::KEYS as $key) {
            if ($key === 'logo') {
                continue;
            }
            $value = Request::post($key, null);
            if ($value === null) {
                if (in_array($key, ['backup_automatico', 'email_integracao', 'whatsapp_integracao'], true)) {
                    $data[$key] = '0';
                }
                continue;
            }
            $data[$key] = is_string($value) ? trim($value) : (string) $value;
        }
        foreach (['backup_automatico', 'email_integracao', 'whatsapp_integracao'] as $boolKey) {
            $data[$boolKey] = Request::has($boolKey) ? '1' : '0';
        }

        $newLogo = null;
        try {
            $newLogo = UploadService::handle(Request::file('logo'), 'config', 'image');
            if ($newLogo !== null) {
                $data['logo'] = $newLogo;
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            $this->redirect('config.index');
        }
        if ($newLogo === null && $removeLogo) {
            $data['logo'] = '';
        }

        try {
            Database::transaction(function () use ($data): void {
                foreach ($data as $key => $value) {
                    Database::query(
                        'INSERT INTO configuracoes (chave, valor) VALUES (:chave, :valor)
                         ON DUPLICATE KEY UPDATE valor = VALUES(valor)',
                        ['chave' => $key, 'valor' => $value]
                    );
                }
            });
        } catch (\Throwable $e) {
            if ($newLogo !== null) {
                UploadService::delete($newLogo);
            }
            throw $e;
        }

        if (($newLogo !== null || $removeLogo) && $currentLogo !== '') {
            UploadService::delete($currentLogo);
        }

        log_action('atualizou_configuracoes', 'configuracoes', null, 'Configurações atualizadas.');
        flash('success', 'Configurações salvas com sucesso.');
        $this->redirect('config.index');
    }

    public function backup(): void
    {
        $current = user();
        if (($current['perfil_slug'] ?? '') !== 'administrador') {
            flash('error', 'Apenas administradores podem gerar backup.');
            $this->redirect('config.index');
        }

        $download = trim((string) Request::get('download', ''));
        $backupDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        if ($download !== '') {
            $safe = basename($download);
            $full = $backupDir . DIRECTORY_SEPARATOR . $safe;
            $realBase = realpath($backupDir);
            $realFile = realpath($full);
            if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase) || !is_file($realFile)) {
                flash('error', 'Arquivo de backup não encontrado.');
                $this->redirect('config.index');
            }
            log_action('baixou_backup', 'configuracoes', null, 'Backup baixado: ' . $safe);
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $safe . '"');
            header('Content-Length: ' . (string) filesize($realFile));
            readfile($realFile);
            exit;
        }

        $config = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');
        $db = (string) ($config['database'] ?? '');
        $user = (string) ($config['username'] ?? '');
        $pass = (string) ($config['password'] ?? '');

        $mysqldump = $this->findMysqldump();
        if ($mysqldump === null) {
            flash('error', 'mysqldump não encontrado no servidor. Backup automático indisponível neste ambiente.');
            $this->redirect('config.index');
        }

        $filename = 'petcare_' . date('Y-m-d_H-i-s') . '.sql';
        $target = $backupDir . DIRECTORY_SEPARATOR . $filename;
        $cmd = escapeshellarg($mysqldump)
            . ' --host=' . escapeshellarg($host)
            . ' --port=' . escapeshellarg($port)
            . ' --user=' . escapeshellarg($user);
        if ($pass !== '') {
            $cmd .= ' --password=' . escapeshellarg($pass);
        }
        $cmd .= ' --single-transaction --routines --triggers --databases ' . escapeshellarg($db)
            . ' --result-file=' . escapeshellarg($target) . ' 2>&1';

        $output = shell_exec($cmd);
        if (!is_file($target) || filesize($target) === 0) {
            if (is_file($target)) {
                @unlink($target);
            }
            flash('error', 'Falha ao gerar backup. Verifique se o mysqldump está disponível e acessível.');
            $this->redirect('config.index');
        }

        log_action('gerou_backup', 'configuracoes', null, 'Backup gerado: ' . $filename);
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($target));
        readfile($target);
        exit;
    }

    private function allSettings(): array
    {
        $settings = [];
        foreach (self::KEYS as $key) {
            $settings[$key] = '';
        }
        try {
            $rows = Database::fetchAll('SELECT chave, valor FROM configuracoes');
            foreach ($rows as $row) {
                $k = (string) ($row['chave'] ?? '');
                if (array_key_exists($k, $settings)) {
                    $settings[$k] = (string) ($row['valor'] ?? '');
                }
            }
        } catch (\Throwable) {
        }
        return $settings;
    }

    private function findMysqldump(): ?string
    {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'mysqldump',
        ];
        foreach ($candidates as $candidate) {
            if (str_contains($candidate, '\\') || str_contains($candidate, '/')) {
                if (is_file($candidate)) {
                    return $candidate;
                }
                continue;
            }
            $which = shell_exec('where ' . escapeshellarg($candidate) . ' 2>NUL');
            if (is_string($which) && trim($which) !== '') {
                $line = trim(strtok($which, "\r\n"));
                if ($line !== '' && is_file($line)) {
                    return $line;
                }
            }
        }
        return null;
    }
}
