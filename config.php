<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'BSC Elektronik TV Parcalari',
    'app_url' => rtrim((string) ($_ENV['APP_URL'] ?? ''), '/'),
    'currency' => $_ENV['APP_CURRENCY'] ?? 'TRY',
    'db' => [
        'connection' => $_ENV['DB_CONNECTION'] ?? 'mysql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_DATABASE'] ?? 'bsc_elektronik',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ],
    'uploads' => [
        'products_dir' => $_ENV['UPLOAD_PRODUCTS_DIR'] ?? 'storage/uploads/products',
        'products_url' => $_ENV['UPLOAD_PRODUCTS_URL'] ?? 'storage/uploads/products',
        'payment_proofs_dir' => $_ENV['UPLOAD_PAYMENT_PROOFS_DIR'] ?? 'storage/uploads/payment-proofs',
        'payment_proofs_url' => $_ENV['UPLOAD_PAYMENT_PROOFS_URL'] ?? 'storage/uploads/payment-proofs',
    ],
    'payment' => [
        'iban' => $_ENV['PAYMENT_IBAN'] ?? 'TR00 0000 0000 0000 0000 0000 00',
        'account_name' => $_ENV['PAYMENT_ACCOUNT_NAME'] ?? 'BSC Elektronik',
        'bank_name' => $_ENV['PAYMENT_BANK_NAME'] ?? 'Banka Adi',
    ],
    'mail' => [
        'transport' => $_ENV['MAIL_TRANSPORT'] ?? 'mail',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@localhost',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? ($_ENV['APP_NAME'] ?? 'BSC Elektronik'),
    ],
];
