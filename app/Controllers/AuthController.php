<?php

declare(strict_types=1);

namespace PetCare\Controllers;

use PetCare\Core\Controller;
use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Core\Session;

final class AuthController extends Controller
{
    public function login(): void
    {
        if (auth()) {
            redirect(login_destination());
        }

        $this->render('auth/login', ['title' => 'Acessar o PetCare'], 'auth');
    }

    public function authenticate(): void
    {
        $email = trim((string) Request::post('email', ''));
        $password = (string) Request::post('senha', '');
        set_old(['email' => $email]);

        $config = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'security.php';
        $lockedUntil = (int) Session::get('login_lock_until', 0);
        if ($lockedUntil > time()) {
            flash('error', 'Muitas tentativas falhas. Tente novamente em ' . ceil(($lockedUntil - time()) / 60) . ' minuto(s).');
            redirect('auth.login');
        }

        if ($email === '' || $password === '') {
            flash('error', 'Informe e-mail e senha.');
            redirect('auth.login');
        }

        $record = Database::fetch(
            'SELECT u.*, p.nome AS perfil_nome, p.slug AS perfil_slug
             FROM usuarios u INNER JOIN perfis p ON p.id = u.perfil_id
             WHERE u.email = :email LIMIT 1',
            ['email' => $email]
        );

        $valid = $record !== null && (int) $record['ativo'] === 1 && password_verify($password, (string) $record['senha']);

        if (!$valid) {
            $attempts = (int) Session::get('login_attempts', 0) + 1;
            Session::set('login_attempts', $attempts);
            if ($attempts >= (int) $config['login_max_attempts']) {
                Session::set('login_lock_until', time() + (int) $config['login_lock_seconds']);
                Session::set('login_attempts', 0);
                flash('error', 'Acesso bloqueado temporariamente por segurança.');
            } else {
                flash('error', 'E-mail ou senha incorretos. Tentativa ' . $attempts . ' de ' . $config['login_max_attempts'] . '.');
            }
            redirect('auth.login');
        }

        if (password_needs_rehash((string) $record['senha'], PASSWORD_BCRYPT, ['cost' => (int) $config['password_cost']])) {
            Database::update('usuarios', ['senha' => password_hash($password, PASSWORD_BCRYPT, ['cost' => (int) $config['password_cost']])], 'id = :id', ['id' => $record['id']]);
        }

        Session::regenerate();
        Session::set('user_id', (int) $record['id']);
        Session::forget('login_attempts');
        Session::forget('login_lock_until');
        Database::update('usuarios', ['ultimo_acesso' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $record['id']]);
        log_action('login', 'autenticacao', (int) $record['id'], 'Login realizado com sucesso.');
        clear_old();

        if ((int) $record['trocar_senha'] === 1) {
            flash('info', 'Por segurança, altere sua senha inicial antes de continuar.');
            redirect('auth.change-password');
        }

        flash('success', 'Bem-vindo(a), ' . explode(' ', (string) $record['nome'])[0] . '!');
        redirect(login_destination($record));
    }

    public function changePassword(): void
    {
        if (!auth()) {
            redirect('auth.login');
        }
        $this->render('auth/change-password', ['title' => 'Alterar senha', 'errors' => []], 'auth');
    }

    public function updatePassword(): void
    {
        $current = (string) Request::post('senha_atual', '');
        $new = (string) Request::post('senha_nova', '');
        $confirmation = (string) Request::post('senha_confirmacao', '');
        $errors = [];

        if ($current === '') {
            $errors['senha_atual'] = 'Informe a senha atual.';
        }
        if (mb_strlen($new) < 8) {
            $errors['senha_nova'] = 'A nova senha deve ter no mínimo 8 caracteres.';
        }
        if ($new !== $confirmation) {
            $errors['senha_confirmacao'] = 'As senhas não conferem.';
        }

        $config = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'security.php';
        $record = Database::fetch('SELECT senha FROM usuarios WHERE id = :id LIMIT 1', ['id' => user()['id']]);
        if ($record === null || !password_verify($current, (string) $record['senha'])) {
            $errors['senha_atual'] = 'Senha atual incorreta.';
        }

        if ($errors !== []) {
            $this->render('auth/change-password', ['title' => 'Alterar senha', 'errors' => $errors], 'auth');
            return;
        }

        Database::update('usuarios', [
            'senha' => password_hash($new, PASSWORD_BCRYPT, ['cost' => (int) $config['password_cost']]),
            'trocar_senha' => 0,
        ], 'id = :id', ['id' => user()['id']]);
        log_action('alterou_senha', 'seguranca', (int) user()['id'], 'Senha atualizada pelo próprio usuário.');
        flash('success', 'Senha alterada com sucesso.');
        redirect(login_destination([
            'trocar_senha' => 0,
            'perfil_slug' => user()['perfil_slug'] ?? '',
        ]));
    }

    public function logout(): void
    {
        $id = (int) Session::get('user_id', 0);
        if ($id > 0) {
            log_action('logout', 'autenticacao', $id, 'Sessão encerrada.');
        }
        Session::destroy();
        Session::start();
        flash('success', 'Você saiu do sistema com segurança.');
        redirect('auth.login');
    }
}
