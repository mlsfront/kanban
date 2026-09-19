<?php
// core/security.php

/**
 * Inicializa a sessão com atributos seguros quando ainda não existe uma sessão.
 */
function ensure_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function csrf_token(): string
{
    ensure_secure_session();
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function require_valid_csrf(): void
{
    ensure_secure_session();
    $provided = $_POST['_csrf_token'] ?? '';
    $expected = $_SESSION['_csrf_token'] ?? '';
    if (!is_string($provided) || !is_string($expected) || $expected === '' ||
        !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit('Requisição inválida. Atualize a página e tente novamente.');
    }
}

function configured_app_url(): string
{
    $url = getenv('APP_URL');
    if (!is_string($url) || $url === '') {
        $url = defined('APP_URL') ? APP_URL : '';
    }
    return rtrim($url, '/') . '/';
}
