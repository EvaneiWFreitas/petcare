<?php

declare(strict_types=1);

namespace PetCare\Core;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $name = Env::get('SESSION_NAME', 'petcare_session');
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::$started = true;

        if (!isset($_SESSION['csrf_token'], $_SESSION['csrf_time'])) {
            self::renewCsrf();
        }
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
        self::renewCsrf();
    }

    public static function renewCsrf(): void
    {
        self::start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_time'] = time();
    }

    public static function csrfToken(): string
    {
        self::start();
        return (string) ($_SESSION['csrf_token'] ?? '');
    }

    public static function validCsrf(?string $token): bool
    {
        self::start();
        $ttl = (int) Env::get('CSRF_TTL', '7200');
        $issued = (int) ($_SESSION['csrf_time'] ?? 0);
        $expected = (string) ($_SESSION['csrf_token'] ?? '');
        if ($expected === '' || !is_string($token)) {
            return false;
        }
        if (($issued + $ttl) < time()) {
            self::renewCsrf();
            return false;
        }
        return hash_equals($expected, $token);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $type, string $message): void
    {
        self::start();
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlashes(): array
    {
        self::start();
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$started = false;
    }
}
