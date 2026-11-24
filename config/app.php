<?php

declare(strict_types=1);

return [
    'name' => 'PetCare',
    'slogan' => 'Cuidando de quem faz parte da sua família.',
    'url' => \PetCare\Core\Env::get('APP_URL', 'http://localhost/petcare/public'),
    'debug' => filter_var(\PetCare\Core\Env::get('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'env' => \PetCare\Core\Env::get('APP_ENV', 'local'),
    'timezone' => \PetCare\Core\Env::get('TIMEZONE', 'America/Sao_Paulo'),
    'key' => \PetCare\Core\Env::get('APP_KEY', ''),
    'per_page' => 15,
    'uploads' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
    'storage' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage',
];
