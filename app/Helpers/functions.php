<?php

declare(strict_types=1);

use PetCare\Core\Database;
use PetCare\Core\Request;
use PetCare\Core\Session;
use PetCare\Services\UploadService;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $route = 'dashboard.index', array $params = []): string
    {
        if (str_starts_with($route, 'http://') || str_starts_with($route, 'https://')) {
            return $route;
        }
        $query = array_merge(['r' => $route], $params);
        return 'index.php?' . http_build_query($query);
    }
}

if (!function_exists('public_url')) {
    function public_url(string $path): string
    {
        $path = ltrim($path, '/');
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($scriptDir === '.') {
            $scriptDir = '';
        }
        $documentRoot = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $publicDir = str_replace('\\', '/', dirname(__DIR__, 2) . '/public');
        $publicReal = realpath($publicDir);
        $scriptReal = $documentRoot !== '' ? realpath($documentRoot . $scriptDir) : false;

        if ($publicReal !== false && $scriptReal === $publicReal) {
            return $scriptDir . '/' . $path;
        }

        if ($publicReal !== false) {
            return $scriptDir . '/public/' . $path;
        }

        return $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return public_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('upload_url')) {
    function upload_url(string $path): string
    {
        return public_url(ltrim($path, '/'));
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = [], ?string $layout = 'main'): void
    {
        $root = dirname(__DIR__, 2);
        $file = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View não encontrada: {$name}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = (string) ob_get_clean();

        if ($layout === null || $layout === '') {
            echo $content;
            return;
        }
        $layoutFile = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout não encontrado: {$layout}");
        }
        require $layoutFile;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $route): never
    {
        $target = url($route);
        if (!headers_sent()) {
            header('Location: ' . $target, true, 302);
        }
        exit;
    }
}

if (!function_exists('redirect_back')) {
    function redirect_back(string $default = 'dashboard.index'): never
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $parts = parse_url($referer);
        $path = is_array($parts) ? (string) ($parts['path'] ?? '') : '';
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';

        if ($path === '' || str_starts_with($path, '//')) {
            redirect($default);
        }
        if ($host !== '') {
            $currentHost = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
            $currentPort = (int) ($_SERVER['SERVER_PORT'] ?? 80);
            $refererPort = (int) ($parts['port'] ?? (($parts['scheme'] ?? '') === 'https' ? 443 : 80));
            if ($host !== $currentHost || $refererPort !== $currentPort) {
                redirect($default);
            }
        }

        $target = $path . (is_array($parts) && isset($parts['query']) ? '?' . $parts['query'] : '');
        if (!headers_sent()) {
            header('Location: ' . $target, true, 302);
        }
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}

if (!function_exists('flash_messages')) {
    function flash_messages(): array
    {
        return Session::pullFlashes();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('set_old')) {
    function set_old(array $data): void
    {
        Session::set('old', $data);
    }
}

if (!function_exists('clear_old')) {
    function clear_old(): void
    {
        Session::forget('old');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Session::csrfToken()) . '">';
    }
}

if (!function_exists('csrf_validate')) {
    function csrf_validate(): bool
    {
        $token = Request::post('_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        return Session::validCsrf(is_string($token) ? $token : null);
    }
}

if (!function_exists('render_action')) {
    function render_action(string $route, array $params, array $action): string
    {
        $href = url($route, $params);
        $btnClass = 'btn btn-sm ' . ($action['class'] ?? 'btn-outline-secondary');
        $icon = (string) ($action['icon'] ?? 'circle-info');
        $label = (string) ($action['label'] ?? 'Ação');
        $confirm = (string) ($action['confirm'] ?? '');
        $text = !empty($action['showLabel']) ? '<span class="d-none d-sm-inline ms-1">' . e($label) . '</span>' : '';

        if ((string) ($action['method'] ?? 'get') === 'post') {
            $script = $confirm !== '' ? ' onsubmit="return confirm(\'' . e($confirm) . '\')"' : '';
            $hidden = csrf_field();
            foreach ($params as $key => $value) {
                if (is_scalar($value)) {
                    $hidden .= '<input type="hidden" name="' . e((string) $key) . '" value="' . e((string) $value) . '">';
                }
            }
            return '<form method="post" action="' . e($href) . '"' . $script . '>'
                . $hidden
                . '<button type="submit" class="' . e($btnClass) . '" title="' . e($label) . '"><i class="fa-solid fa-' . e($icon) . '"></i>' . $text . '</button>'
                . '</form>';
        }

        $script = $confirm !== '' ? ' onclick="return confirm(\'' . e($confirm) . '\')"' : '';
        return '<a href="' . e($href) . '" class="' . e($btnClass) . '" title="' . e($label) . '"' . $script . '><i class="fa-solid fa-' . e($icon) . '"></i>' . $text . '</a>';
    }
}

if (!function_exists('delete_anexos')) {
    function delete_anexos(string $entidade, int $id): void
    {
        $params = ['entidade' => $entidade, 'id' => $id];
        $rows = Database::fetchAll('SELECT caminho FROM anexos WHERE entidade = :entidade AND entidade_id = :id', $params);
        Database::delete('anexos', 'entidade = :entidade AND entidade_id = :id', $params);
        foreach ($rows as $row) {
            UploadService::delete((string) ($row['caminho'] ?? ''));
        }
    }
}

if (!function_exists('user')) {
    function user(): ?array
    {
        static $cached = null;
        $id = (int) Session::get('user_id', 0);
        if ($id <= 0) {
            return null;
        }
        if (is_array($cached) && (int) ($cached['id'] ?? 0) === $id) {
            return $cached;
        }

        $row = Database::fetch(
            'SELECT u.id, u.nome, u.email, u.foto, u.perfil_id, u.trocar_senha, p.nome AS perfil_nome, p.slug AS perfil_slug
             FROM usuarios u INNER JOIN perfis p ON p.id = u.perfil_id
             WHERE u.id = :id AND u.ativo = 1 LIMIT 1',
            ['id' => $id]
        );
        if ($row === null) {
            Session::destroy();
            return null;
        }

        $permissions = [];
        foreach (Database::fetchAll('SELECT modulo, acao FROM permissoes WHERE perfil_id = :id', ['id' => $row['perfil_id']]) as $permission) {
            $permissions[$permission['modulo']][] = $permission['acao'];
        }
        $row['permissoes'] = $permissions;
        $cached = $row;
        return $row;
    }
}

if (!function_exists('auth')) {
    function auth(): bool
    {
        return user() !== null;
    }
}

if (!function_exists('can')) {
    function can(string $module, string $action = 'view'): bool
    {
        $current = user();
        if ($current === null) {
            return false;
        }
        if ($current['perfil_slug'] === 'administrador') {
            return true;
        }
        return in_array($action, $current['permissoes'][$module] ?? [], true);
    }
}

if (!function_exists('authorize')) {
    function authorize(string $module, string $action = 'view'): void
    {
        if (!auth()) {
            flash('error', 'Faça login para acessar esta área.');
            redirect('auth.login');
        }
        if (!can($module, $action)) {
            abort(403, 'Você não tem permissão para executar esta ação.');
        }
    }
}

if (!function_exists('abort')) {
    function abort(int $code = 404, string $message = 'Página não encontrada.'): never
    {
        http_response_code($code);
        view('common/error', ['code' => $code, 'message' => $message], 'error');
        exit;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        static $cache = [];
        if (!array_key_exists($key, $cache)) {
            try {
                $row = Database::fetch('SELECT valor FROM configuracoes WHERE chave = :key LIMIT 1', ['key' => $key]);
                $cache[$key] = $row['valor'] ?? $default;
            } catch (Throwable) {
                $cache[$key] = $default;
            }
        }
        return (string) $cache[$key];
    }
}

if (!function_exists('brand_logo')) {
    function brand_logo(string $imageClass = 'brand-logo-img', string $fallbackClass = ''): string
    {
        $logo = setting('logo');
        $name = setting('clinica_nome', 'PetCare');
        if ($logo !== '') {
            return '<img src="' . e(upload_url($logo)) . '" alt="' . e($name) . '" class="' . e($imageClass) . '">';
        }

        return '<span class="' . e(trim($fallbackClass)) . '" aria-hidden="true"><i class="fa-solid fa-paw"></i></span>';
    }
}

if (!function_exists('favicon_url')) {
    function favicon_url(): string
    {
        $logo = setting('logo');
        if ($logo !== '') {
            return upload_url($logo);
        }

        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%2320b8b0'/%3E%3Ctext x='32' y='43' font-size='34' font-family='Arial' font-weight='bold' text-anchor='middle' fill='white'%3EP%3C/text%3E%3C/svg%3E";
    }
}

if (!function_exists('log_action')) {
    function log_action(string $action, string $module, ?int $recordId = null, ?string $details = null): void
    {
        try {
            Database::insert('logs', [
                'usuario_id' => (int) Session::get('user_id', 0) ?: null,
                'acao' => mb_substr($action, 0, 80),
                'modulo' => mb_substr($module, 0, 80),
                'registro_id' => $recordId,
                'detalhes' => $details,
                'ip' => request_ip(),
                'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            ]);
        } catch (Throwable) {
        }
    }
}

if (!function_exists('request_ip')) {
    function request_ip(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ip = trim(explode(',', (string) $ip)[0]);
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

if (!function_exists('money')) {
    function money(mixed $value): string
    {
        return 'R$ ' . number_format((float) ($value ?? 0), 2, ',', '.');
    }
}

if (!function_exists('date_br')) {
    function date_br(?string $date, bool $withTime = false): string
    {
        if ($date === null || $date === '' || $date === '0000-00-00') {
            return '—';
        }
        try {
            $dt = new DateTime($date);
            return $dt->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
        } catch (Exception) {
            return '—';
        }
    }
}

if (!function_exists('age')) {
    function age(?string $birthDate): string
    {
        if ($birthDate === null || $birthDate === '') {
            return '—';
        }
        try {
            $birth = new DateTime($birthDate);
            $diff = $birth->diff(new DateTime());
            if ($diff->y > 0) {
                return $diff->y . ' ' . ($diff->y === 1 ? 'ano' : 'anos');
            }
            if ($diff->m > 0) {
                return $diff->m . ' ' . ($diff->m === 1 ? 'mês' : 'meses');
            }
            return max(0, $diff->d) . ' ' . ($diff->d === 1 ? 'dia' : 'dias');
        } catch (Exception) {
            return '—';
        }
    }
}

if (!function_exists('mask_cpf')) {
    function mask_cpf(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if (strlen($digits) !== 11) {
            return (string) $value;
        }
        return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9);
    }
}

if (!function_exists('gerar_codigo')) {
    function gerar_codigo(string $prefix, string $table): string
    {
        Database::identifier($table);
        $prefix = strtoupper(preg_replace('/[^a-zA-Z]/', '', $prefix) ?? 'REG');
        $next = (int) Database::scalar('SELECT COUNT(*) + 1 FROM ' . Database::identifier($table));
        return sprintf('%s-%d-%05d', $prefix, (int) date('Y'), $next);
    }
}

if (!function_exists('status_badge')) {
    function status_badge(?string $status): string
    {
        $labels = [
            'aguardando' => 'Aguardando', 'confirmado' => 'Confirmado', 'em_atendimento' => 'Em atendimento',
            'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado', 'falta' => 'Não compareceu',
            'aberto' => 'Em aberto', 'pago' => 'Pago', 'parcial' => 'Parcial', 'vencido' => 'Vencido',
            'ativo' => 'Ativo', 'inativo' => 'Inativo', 'pendente' => 'Pendente', 'recebido' => 'Recebido',
            'internado' => 'Internado', 'alta' => 'Alta', 'agendada' => 'Agendada', 'realizada' => 'Realizada',
            'solicitado' => 'Solicitado', 'liberado' => 'Liberado',
        ];
        $status = (string) $status;
        $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
        $good = ['confirmado', 'finalizado', 'pago', 'ativo', 'recebido', 'alta', 'realizada', 'liberado'];
        $bad = ['cancelado', 'falta', 'vencido', 'inativo', 'pendente'];
        $warning = ['aguardando', 'em_atendimento', 'aberto', 'parcial', 'agendada', 'solicitado', 'internado'];
        $class = in_array($status, $good, true) ? 'success' : (in_array($status, $bad, true) ? 'danger' : (in_array($status, $warning, true) ? 'warning text-dark' : 'secondary'));
        return '<span class="badge bg-' . $class . ' rounded-pill">' . e($label) . '</span>';
    }
}

if (!function_exists('selected')) {
    function selected(mixed $current, mixed $value): string
    {
        return (string) $current === (string) $value ? 'selected' : '';
    }
}

if (!function_exists('checked')) {
    function checked(mixed $current, mixed $value = 1): string
    {
        return (string) $current === (string) $value ? 'checked' : '';
    }
}

if (!function_exists('pagination')) {
    function pagination(array $paginator, string $route, array $params = []): string
    {
        if ((int) $paginator['pages'] <= 1) {
            return '';
        }
        $page = (int) $paginator['page'];
        $pages = (int) $paginator['pages'];
        $html = '<nav aria-label="Paginação"><ul class="pagination pagination-sm justify-content-end mb-0">';
        $html .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . e(url($route, $params + ['page' => max(1, $page - 1)])) . '">Anterior</a></li>';
        $start = max(1, $page - 2);
        $end = min($pages, $page + 2);
        for ($i = $start; $i <= $end; $i++) {
            $html .= '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="' . e(url($route, $params + ['page' => $i])) . '">' . $i . '</a></li>';
        }
        $html .= '<li class="page-item ' . ($page >= $pages ? 'disabled' : '') . '"><a class="page-link" href="' . e(url($route, $params + ['page' => min($pages, $page + 1)])) . '">Próxima</a></li>';
        return $html . '</ul></nav>';
    }
}
