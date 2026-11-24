<?php

declare(strict_types=1);

return [
    'host' => \PetCare\Core\Env::get('DB_HOST', '127.0.0.1'),
    'port' => (int) \PetCare\Core\Env::get('DB_PORT', '3306'),
    'dbname' => \PetCare\Core\Env::get('DB_NAME', 'petcare_db'),
    'username' => \PetCare\Core\Env::get('DB_USER', 'root'),
    'password' => \PetCare\Core\Env::get('DB_PASS', ''),
    'charset' => \PetCare\Core\Env::get('DB_CHARSET', 'utf8mb4'),
    'dsn' => sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        \PetCare\Core\Env::get('DB_HOST', '127.0.0.1'),
        (int) \PetCare\Core\Env::get('DB_PORT', '3306'),
        \PetCare\Core\Env::get('DB_NAME', 'petcare_db'),
        \PetCare\Core\Env::get('DB_CHARSET', 'utf8mb4')
    ),
];
