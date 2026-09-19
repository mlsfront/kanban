<?php
require_once __DIR__ . '/../core/security.php';

session_id('kanban-test-' . bin2hex(random_bytes(4)));
ensure_secure_session();
$token = csrf_token();
if (strlen($token) !== 64) {
    throw new RuntimeException('Token CSRF não possui o tamanho esperado.');
}
if (!hash_equals($token, $_SESSION['_csrf_token'])) {
    throw new RuntimeException('Token CSRF não foi persistido na sessão.');
}
if (hash_equals($token, str_repeat('0', 64))) {
    throw new RuntimeException('Token CSRF inválido foi aceito.');
}
echo "security_test: ok\n";
