<?php

declare(strict_types=1);

use PetCare\Core\Autoloader;
use PetCare\Core\Env;
use PetCare\Core\Router;
use PetCare\Core\Session;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php';

Autoloader::register();
Env::load();
Session::start();

$app = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
date_default_timezone_set($app['timezone']);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'functions.php';

ini_set('display_errors', $app['debug'] ? '1' : '0');
error_reporting($app['debug'] ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    Router::dispatch();
} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $log = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'errors.log';
    @file_put_contents($log, '[' . date('Y-m-d H:i:s') . '] ' . get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    view('common/error', [
        'code' => 500,
        'message' => $app['debug'] ? $e->getMessage() : 'Ocorreu um erro interno. Tente novamente mais tarde.',
        'trace' => $app['debug'] ? $e->getTraceAsString() : null,
    ], 'error');
}
