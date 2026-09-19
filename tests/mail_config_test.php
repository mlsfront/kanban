<?php
require_once __DIR__ . '/../src/Config/Mail.php';

putenv('MAIL_FROM_ADDRESS=noreply@example.com');
if (filter_var(App\Config\Mail::fromAddress(), FILTER_VALIDATE_EMAIL) === false) {
    throw new RuntimeException('Remetente válido foi rejeitado.');
}

putenv('MAIL_FROM_ADDRESS=noreply@localhost');
if (filter_var(App\Config\Mail::fromAddress(), FILTER_VALIDATE_EMAIL) !== false) {
    throw new RuntimeException('Remetente localhost deveria ser rejeitado pelo teste.');
}

echo "mail_config_test: PASS\n";
