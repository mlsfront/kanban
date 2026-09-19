
<?php
// core/config.php
require_once __DIR__ . '/env.php';

// A URL pública deve ser configurada por ambiente e nunca derivada de HTTP_HOST.
$configuredUrl = getenv('APP_URL');
if (!is_string($configuredUrl) || trim($configuredUrl) === '') {
    throw new RuntimeException('Variável de ambiente obrigatória ausente: APP_URL');
}

if (!preg_match('#^https?://[^/]+(?:/[^/]*)*/?$#i', $configuredUrl)) {
    throw new RuntimeException('APP_URL inválida. Configure uma URL http(s) absoluta.');
}

define('BASE_URL', rtrim($configuredUrl, '/') . '/');

// Helper function para redirecionamento seguro
function redirect($path) {
    // rtrim e ltrim evitam barras duplicadas na URL (ex: http://site.com//login)
    header("Location: " . rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'));
    exit;
}
?>
