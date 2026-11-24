<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Core\Session;
use PetCare\Services\UploadService;
use PetCare\Validators\Validator;

final class UsuarioController extends Controller
{
    private const PER_PAGE = 15;
    private const MODULES = [
        'dashboard', 'clientes', 'animais', 'agenda', 'atendimentos', 'prontuarios', 'vacinas', 'receitas', 'exames',
        'cirurgias', 'internacoes', 'farmacia', 'financeiro', 'compras', 'banho_tosa', 'usuarios', 'relatorios', 'notificacoes', 'configuracoes', 'portal',
    ];
    private const ACTIONS = ['view', 'create', 'edit', 'delete', 'print', 'approve'];

    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $search = trim((string) Request::get('q', ''));
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.nome LIKE :q OR u.email LIKE :q OR p.nome LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $total = (int) Database::scalar(
            'SELECT COUNT(*) FROM usuarios u INNER JOIN perfis p ON p.id = u.perfil_id' . $sqlWhere,
            $params
        );
        $paginator = $this->paginator($total, $page);
        $params['limit'] = $paginator['perPage'];
        $params['offset'] = $paginator['offset'];
        $rows = Database::fetchAll(
            'SELECT u.id, u.nome, u.email, u.foto, p.nome AS perfil, u.ultimo_acesso,
                    CASE WHEN u.ativo = 1 THEN "ativo" ELSE "inativo" END AS status
             FROM usuarios u
             INNER JOIN perfis p ON p.id = u.perfil_id' . $sqlWhere . '
             ORDER BY u.nome ASC
             LIMIT :limit OFFSET :offset',
            $params
        );
        foreach ($rows as &$row) {
            $row['ultimo_acesso'] = $row['ultimo_acesso'] ? date_br((string) $row['ultimo_acesso'], true) : '—';
        }
        unset($row);
        $this->render('usuarios/index', [
            'title' => 'Usuários',
            'rows' => $rows,
            'search' => $search,
            'paginator' => $paginator,
        ]);
    }

    public function create(): void
    {
        $this->render('usuarios/form', [
            'title' => 'Novo usuário',
            'values' => $this->withOld($this->defaults()),
            'errors' => [],
            'perfis' => $this->perfis(),
            'action' => 'usuarios.store',
            'isEdit' => false,
            'enctype' => 'multipart/form-data',
        ]);
    }

    public function store(): void
    {
        $data = $this->collect();
        $errors = (new Validator($this->rules(false)))->validate($data + ['id' => 0]);
        $photo = null;

        if ($errors === []) {
            try {
                $photo = UploadService::handle(Request::file('foto'), 'usuarios', 'image');
            } catch (\RuntimeException $e) {
                $errors['foto'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            set_old($data);
            $this->render('usuarios/form', [
                'title' => 'Novo usuário',
                'values' => $data + ['foto' => ''],
                'errors' => $errors,
                'perfis' => $this->perfis(),
                'action' => 'usuarios.store',
                'isEdit' => false,
                'enctype' => 'multipart/form-data',
            ]);
            return;
        }
        $security = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'security.php';
        $cost = (int) ($security['password_cost'] ?? 12);
        try {
            $id = Database::insert('usuarios', [
                'nome' => $data['nome'],
                'email' => $data['email'],
                'foto' => $photo,
                'senha' => password_hash((string) $data['senha'], PASSWORD_DEFAULT, ['cost' => $cost]),
                'perfil_id' => (int) $data['perfil_id'],
                'funcionario_id' => null,
                'ativo' => (int) $data['ativo'],
                'trocar_senha' => (int) $data['trocar_senha'],
                'ultimo_acesso' => null,
            ]);
        } catch (\Throwable $e) {
            if ($photo !== null) {
                UploadService::delete($photo);
            }
            throw $e;
        }
        log_action('criou_usuario', 'usuarios', $id, 'Usuário ' . $data['nome'] . ' criado.');
        clear_old();
        flash('success', 'Usuário cadastrado com sucesso.');
        $this->redirect('usuarios.index');
    }

    public function edit(): void
    {
        $id = (int) Request::get('id', 0);
        $usuario = $this->find($id);
        if ($usuario === null) {
            abort(404, 'Usuário não encontrado.');
        }
        $values = $this->withOld([
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'foto' => $usuario['foto'],
            'perfil_id' => $usuario['perfil_id'],
            'ativo' => (int) $usuario['ativo'],
            'trocar_senha' => (int) $usuario['trocar_senha'],
            'nova_senha' => '',
            'remover_foto' => 0,
        ]);
        $this->render('usuarios/form', [
            'title' => 'Editar usuário',
            'values' => $values,
            'errors' => [],
            'perfis' => $this->perfis(),
            'action' => 'usuarios.update',
            'isEdit' => true,
            'enctype' => 'multipart/form-data',
        ]);
    }

    public function update(): void
    {
        $id = (int) Request::post('id', 0);
        $usuario = $this->find($id);
        if ($usuario === null) {
            abort(404, 'Usuário não encontrado.');
        }
        $removePhoto = Request::has('remover_foto');
        $data = $this->collect($id);
        $rules = $this->rules(true);
        $novaSenha = trim((string) Request::post('nova_senha', ''));
        if ($novaSenha !== '') {
            $rules['nova_senha'] = 'min:8';
            $data['nova_senha'] = $novaSenha;
        } else {
            $data['nova_senha'] = '';
        }
        $errors = (new Validator($rules))->validate($data + ['id' => $id]);
        $photo = null;

        if ($errors === []) {
            try {
                $photo = UploadService::handle(Request::file('foto'), 'usuarios', 'image');
            } catch (\RuntimeException $e) {
                $errors['foto'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            set_old($data + ['id' => $id]);
            $this->render('usuarios/form', [
                'title' => 'Editar usuário',
                'values' => $data + [
                    'id' => $id,
                    'foto' => $usuario['foto'],
                    'remover_foto' => $removePhoto ? 1 : 0,
                ],
                'errors' => $errors,
                'perfis' => $this->perfis(),
                'action' => 'usuarios.update',
                'isEdit' => true,
                'enctype' => 'multipart/form-data',
            ]);
            return;
        }
        $update = [
            'nome' => $data['nome'],
            'email' => $data['email'],
            'perfil_id' => (int) $data['perfil_id'],
            'ativo' => (int) $data['ativo'],
            'trocar_senha' => (int) $data['trocar_senha'],
        ];
        if ($photo !== null) {
            $update['foto'] = $photo;
        } elseif ($removePhoto) {
            $update['foto'] = null;
        }
        if ($novaSenha !== '') {
            $security = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'security.php';
            $cost = (int) ($security['password_cost'] ?? 12);
            $update['senha'] = password_hash($novaSenha, PASSWORD_DEFAULT, ['cost' => $cost]);
        }
        try {
            Database::update('usuarios', $update, 'id = :id', ['id' => $id]);
        } catch (\Throwable $e) {
            if ($photo !== null) {
                UploadService::delete($photo);
            }
            throw $e;
        }
        if (($photo !== null || $removePhoto) && !empty($usuario['foto'])) {
            UploadService::delete((string) $usuario['foto']);
        }
        log_action('editou_usuario', 'usuarios', $id, 'Usuário ' . $data['nome'] . ' atualizado.');
        clear_old();
        flash('success', 'Usuário atualizado com sucesso.');
        $this->redirect('usuarios.index');
    }

    public function permissions(): void
    {
        $perfis = Database::fetchAll('SELECT id, nome, slug FROM perfis ORDER BY id ASC');
        if ($perfis === []) {
            abort(404, 'Nenhum perfil encontrado.');
        }
        $perfilId = (int) Request::get('perfil_id', 0);
        $defaultPerfil = null;
        foreach ($perfis as $p) {
            if (($p['slug'] ?? '') !== 'tutor') {
                $defaultPerfil = (int) $p['id'];
                break;
            }
        }
        if ($defaultPerfil === null) {
            $defaultPerfil = (int) $perfis[0]['id'];
        }
        if ($perfilId <= 0) {
            $perfilId = $defaultPerfil;
        }
        $perfil = null;
        foreach ($perfis as $p) {
            if ((int) $p['id'] === $perfilId) {
                $perfil = $p;
                break;
            }
        }
        if ($perfil === null) {
            abort(404, 'Perfil não encontrado.');
        }
        $rows = Database::fetchAll('SELECT modulo, acao FROM permissoes WHERE perfil_id = :id', ['id' => $perfilId]);
        $granted = [];
        foreach ($rows as $row) {
            $granted[(string) $row['modulo']][(string) $row['acao']] = true;
        }
        $this->render('usuarios/permissions', [
            'title' => 'Permissões por perfil',
            'perfis' => $perfis,
            'perfil' => $perfil,
            'modules' => self::MODULES,
            'actions' => self::ACTIONS,
            'granted' => $granted,
        ]);
    }

    public function savePermissions(): void
    {
        $perfilId = (int) Request::post('perfil_id', (int) Request::get('perfil_id', 0));
        $perfil = Database::fetch('SELECT id, nome FROM perfis WHERE id = :id LIMIT 1', ['id' => $perfilId]);
        if ($perfil === null) {
            abort(404, 'Perfil não encontrado.');
        }
        $posted = Request::post('permissao', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        Database::transaction(function () use ($perfilId, $posted): void {
            Database::delete('permissoes', 'perfil_id = :id', ['id' => $perfilId]);
            foreach (self::MODULES as $module) {
                $moduleActions = $posted[$module] ?? [];
                if (!is_array($moduleActions)) {
                    continue;
                }
                foreach (self::ACTIONS as $action) {
                    if (!array_key_exists($action, $moduleActions)) {
                        continue;
                    }
                    Database::insert('permissoes', [
                        'perfil_id' => $perfilId,
                        'modulo' => $module,
                        'acao' => $action,
                    ]);
                }
            }
        });
        log_action('atualizou_permissoes', 'usuarios', $perfilId, 'Permissões atualizadas para o perfil ' . $perfil['nome'] . '.');
        flash('success', 'Permissões atualizadas com sucesso.');
        header('Location: ' . url('usuarios.permissions', ['perfil_id' => $perfilId]));
        exit;
    }

    public function destroy(?int $id = null): void
    {
        $id = $id ?? (int) Request::post('id', 0);
        $usuario = $id > 0 ? $this->find($id) : null;
        if ($usuario === null) {
            abort(404, 'Usuário não encontrado.');
        }

        if ($id === (int) Session::get('user_id', 0)) {
            flash('error', 'Você não pode inativar o próprio usuário.');
            $this->redirect('usuarios.index');
        }

        if ((int) ($usuario['ativo'] ?? 0) === 0) {
            flash('warning', 'Este usuário já está inativo.');
            $this->redirect('usuarios.index');
        }

        Database::transaction(static fn(): int => Database::update('usuarios', ['ativo' => 0], 'id = :id', ['id' => $id]));
        log_action('inativou_usuario', 'usuarios', $id, 'Usuário inativado.');
        clear_old();
        flash('success', 'Usuário inativado com sucesso.');
        $this->redirect('usuarios.index');
    }

    private function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM usuarios WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    private function perfis(): array
    {
        return Database::fetchAll('SELECT id, nome, slug FROM perfis ORDER BY nome ASC');
    }

    private function defaults(): array
    {
        return [
            'nome' => '',
            'email' => '',
            'perfil_id' => '',
            'senha' => '',
            'nova_senha' => '',
            'ativo' => 1,
            'trocar_senha' => 0,
        ];
    }

    private function collect(int $id = 0): array
    {
        $data = [];
        foreach (['nome', 'email', 'perfil_id', 'senha', 'nova_senha'] as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : $value;
        }
        $data['ativo'] = Request::has('ativo') ? 1 : 0;
        $data['trocar_senha'] = Request::has('trocar_senha') ? 1 : 0;
        return $data;
    }

    private function rules(bool $isEdit): array
    {
        $rules = [
            'nome' => 'required|max:150',
            'email' => 'required|email|max:255|unique:usuarios,email,id',
            'perfil_id' => 'required|integer',
        ];
        if (!$isEdit) {
            $rules['senha'] = 'required|min:8';
        }
        return $rules;
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
