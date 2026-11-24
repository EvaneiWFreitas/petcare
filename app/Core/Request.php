<?php

declare(strict_types=1);

namespace PetCare\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function isAjax(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public static function route(): string
    {
        $route = trim((string) ($_GET['r'] ?? ''));
        if ($route !== '') {
            return preg_replace('/[^a-zA-Z0-9_.\/-]/', '', $route) ?: 'site.home';
        }

        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
        $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }
        $path = trim($path, '/');
        $path = preg_replace('#^index\.php/?#i', '', $path) ?? '';
        $path = trim(str_replace('/', '.', $path), '.');

        return $path !== '' ? $path : 'site.home';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public static function only(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $_POST) || array_key_exists($key, $_GET)) {
                $data[$key] = self::input($key);
            }
        }
        return $data;
    }

    public static function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    public static function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        return is_array($file) ? $file : null;
    }
}
