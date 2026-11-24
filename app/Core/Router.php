<?php

declare(strict_types=1);

namespace PetCare\Core;

final class Router
{
    public static function dispatch(): void
    {
        $routes = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';
        $name = Request::route();
        $route = $routes[$name] ?? null;

        if ($route === null) {
            abort(404, 'A página solicitada não existe.');
        }

        $method = strtoupper($route['method'] ?? 'GET');
        if ($method !== 'ANY' && $method !== Request::method()) {
            abort(405, 'Método HTTP não permitido para esta rota.');
        }

        if (!empty($route['auth']) && !auth()) {
            flash('error', 'Faça login para acessar esta área.');
            redirect('auth.login');
        }

        if (!empty($route['permission']) && !can((string) $route['permission'][0], (string) $route['permission'][1])) {
            abort(403, 'Você não tem permissão para acessar este módulo.');
        }

        if (Request::isPost() && !csrf_validate()) {
            abort(419, 'Sessão expirada. Recarregue a página e tente novamente.');
        }

        $target = explode('@', (string) $route['controller'], 2);
        if (count($target) !== 2) {
            throw new RuntimeException('Rota mal configurada: ' . $name);
        }
        $class = 'PetCare\\Controllers\\' . $target[0];
        if (!class_exists($class)) {
            throw new RuntimeException('Controlador não encontrado: ' . $class);
        }
        $controller = new $class();
        $action = $target[1];
        if (!method_exists($controller, $action)) {
            throw new RuntimeException('Ação não encontrada: ' . $class . '::' . $action);
        }
        $controller->{$action}();
    }
}
