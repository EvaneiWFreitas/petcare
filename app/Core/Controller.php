<?php

declare(strict_types=1);

namespace PetCare\Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        \view($view, $data, $layout);
    }

    protected function redirect(string $route): never
    {
        \redirect($route);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
