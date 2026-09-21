<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'name' => Env::get('APP_NAME', 'Split Pay'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim((string) Env::get('APP_URL', 'http://localhost'), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'UTC'),

    'session' => [
        'name' => Env::get('SESSION_NAME', 'splitpay_session'),
        'lifetime' => (int) Env::get('SESSION_LIFETIME', 7200),
        'secure' => filter_var(Env::get('SESSION_SECURE_COOKIE', 'false'), FILTER_VALIDATE_BOOLEAN),
    ],

    'storage_path' => Env::get('STORAGE_PATH', dirname(__DIR__) . '/storage'),
    'receipt_path' => Env::get('RECEIPT_STORAGE_PATH', dirname(__DIR__) . '/storage/receipts'),
    'avatar_path' => Env::get('AVATAR_STORAGE_PATH', dirname(__DIR__) . '/storage/avatars'),

    'max_receipt_upload_size' => (int) Env::get('MAX_RECEIPT_UPLOAD_SIZE', 10485760),
    'password_reset_expiry_minutes' => (int) Env::get('PASSWORD_RESET_EXPIRY_MINUTES', 60),
    'invitation_expiry_days' => (int) Env::get('INVITATION_EXPIRY_DAYS', 7),
    'login_max_attempts' => (int) Env::get('LOGIN_MAX_ATTEMPTS', 5),
    'login_lockout_minutes' => (int) Env::get('LOGIN_LOCKOUT_MINUTES', 15),

    'mail' => [
        'driver' => Env::get('MAIL_DRIVER', 'log'),
        'from_address' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@splitpay.test'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'Split Pay'),
    ],
];
