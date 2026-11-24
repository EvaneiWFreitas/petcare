<?php

declare(strict_types=1);

namespace PetCare\Services;

use PetCare\Core\Env;

final class UploadService
{
    public static function handle(?array $file, string $directory = 'geral', string $type = 'image'): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Falha ao enviar o arquivo. Código: ' . (int) $file['error']);
        }
        if (!is_uploaded_file((string) $file['tmp_name'])) {
            throw new \RuntimeException('Envio de arquivo inválido.');
        }

        $maxSize = (int) Env::get('UPLOAD_MAX_SIZE', '5242880');
        if ((int) ($file['size'] ?? 0) > $maxSize) {
            throw new \RuntimeException('Arquivo muito grande. Limite: ' . round($maxSize / 1048576, 1) . ' MB.');
        }

        $config = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'security.php';
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowed = $config['allowed_extensions'][$type] ?? $config['allowed_extensions']['document'];
        if (!in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Extensão de arquivo não permitida.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        if (!in_array($mime, $config['allowed_mimes'], true)) {
            throw new \RuntimeException('Tipo MIME de arquivo não permitido.');
        }
        if ($type === 'image' && !@getimagesize((string) $file['tmp_name'])) {
            throw new \RuntimeException('A imagem enviada é inválida.');
        }

        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
        $targetDir = $base . DIRECTORY_SEPARATOR . preg_replace('/[^a-zA-Z0-9_-]/', '', $directory);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Não foi possível criar o diretório de upload.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file((string) $file['tmp_name'], $targetDir . DIRECTORY_SEPARATOR . $name)) {
            throw new \RuntimeException('Não foi possível salvar o arquivo.');
        }

        return 'uploads/' . basename($targetDir) . '/' . $name;
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        $full = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $uploads = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
        if (is_file($full) && str_starts_with(realpath($full) ?: '', realpath($uploads) ?: '')) {
            unlink($full);
        }
    }
}
