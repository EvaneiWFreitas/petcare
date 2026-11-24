<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Services\UploadService;

final class AnexoController extends Controller
{
    public function store(): void
    {
        $entidade = strtolower(trim((string) Request::post('entidade', '')));
        $entidadeId = (int) Request::post('entidade_id', 0);

        try {
            if (preg_match('/^[a-z][a-z0-9_]*$/', $entidade) !== 1) {
                throw new \RuntimeException('Entidade inválida.');
            }
            if ($entidadeId <= 0) {
                throw new \RuntimeException('Identificador do registro inválido.');
            }
            $file = Request::file('anexo');
            if ($file === null) {
                throw new \RuntimeException('Nenhum arquivo foi enviado.');
            }
            $caminho = UploadService::handle($file, 'anexos', 'document');
            if ($caminho === null) {
                throw new \RuntimeException('Nenhum arquivo foi enviado.');
            }

            $id = Database::insert('anexos', [
                'entidade' => $entidade,
                'entidade_id' => $entidadeId,
                'nome_original' => mb_substr((string) ($file['name'] ?? 'anexo'), 0, 255),
                'caminho' => $caminho,
                'mime' => mb_substr((string) ($file['type'] ?? ''), 0, 120),
                'tamanho' => (int) ($file['size'] ?? 0),
                'usuario_id' => (int) (user()['id'] ?? 0) ?: null,
            ]);

            $this->json([
                'success' => true,
                'message' => 'Anexo enviado com sucesso.',
                'id' => $id,
                'caminho' => $caminho,
                'url' => asset($caminho),
                'download_url' => url('anexos.download', ['id' => $id]),
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function download(): void
    {
        $id = (int) Request::get('id', 0);
        $anexo = Database::fetch('SELECT * FROM anexos WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($anexo === null) {
            abort(404, 'Anexo não encontrado.');
        }

        $root = dirname(__DIR__, 2);
        $uploads = realpath($root . '/public/uploads');
        $path = realpath($root . '/public/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $anexo['caminho']));
        if ($uploads === false || $path === false || !str_starts_with($path, $uploads . DIRECTORY_SEPARATOR) || !is_file($path)) {
            abort(404, 'Arquivo do anexo não encontrado.');
        }

        $mime = trim((string) ($anexo['mime'] ?? ''));
        if ($mime === '' || preg_match('/^[a-zA-Z0-9\.+\-\/]+$/', $mime) !== 1) {
            $mime = 'application/octet-stream';
        }
        $name = basename(str_replace('\\', '/', (string) $anexo['nome_original']));
        $name = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $name) ?: 'anexo';
        if (!headers_sent()) {
            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode($name));
            header('Content-Length: ' . (string) filesize($path));
            header('Cache-Control: private, no-cache');
        }
        readfile($path);
        exit;
    }
}
