<?php
// core/auth.php
require_once(__DIR__ . '/security.php');
ensure_secure_session();

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    // Destrói a sessão por precaução
    session_unset();
    session_destroy();
    
    // Redireciona para o login
    require_once(__DIR__ . '/config.php');
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// O ID do usuário autenticado estará disponível para uso em $user_id
$user_id = $_SESSION['usuario_id'];
