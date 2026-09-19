<?php
// core/logger.php

defined('ENABLE_ACCESS_LOG') or define('ENABLE_ACCESS_LOG', true);
require_once(__DIR__ . '/logManager.php');

if (ENABLE_ACCESS_LOG) {
    require_once(__DIR__ . '/database.php');
}

function log_access() {
    // Apenas loga se houver uma sessão de usuário ativa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $user_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
    $pagina = $_SERVER['REQUEST_URI'];
    $metodo = $_SERVER['REQUEST_METHOD'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT INTO logs_acesso (usuario_id, pagina, metodo, ip_address) VALUES (:usuario_id, :pagina, :metodo, :ip_address)");
        $stmt->execute([
            ':usuario_id' => $user_id,
            ':pagina' => $pagina,
            ':metodo' => $metodo,
            ':ip_address' => $ip_address
        ]);
        LogManager::info('access', [
            'usuario_id' => $user_id,
            'pagina' => substr($pagina, 0, 255),
            'metodo' => $metodo,
        ]);
    } catch (Exception $e) {
        // Ignora erro de log para não quebrar a aplicação principal
        error_log("Erro ao registrar log de acesso: " . $e->getMessage());
    }
}

if (ENABLE_ACCESS_LOG) {
    log_access();
}
?>
