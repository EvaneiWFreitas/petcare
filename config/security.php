<?php

declare(strict_types=1);

return [
    'session_name' => \PetCare\Core\Env::get('SESSION_NAME', 'petcare_session'),
    'csrf_ttl' => (int) \PetCare\Core\Env::get('CSRF_TTL', '43200'),
    'password_cost' => (int) \PetCare\Core\Env::get('PASSWORD_COST', '12'),
    'upload_max_size' => (int) \PetCare\Core\Env::get('UPLOAD_MAX_SIZE', '5242880'),
    'allowed_extensions' => [
        'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'document' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    ],
    'allowed_mimes' => [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
    'login_max_attempts' => 5,
    'login_lock_seconds' => 900,
];
