<?php
// admin/index.php
require_once(__DIR__ . '/../core/config.php');
require_once(__DIR__ . '/../core/auth.php');
require_once(__DIR__ . '/../core/database.php');

$conn = get_db_connection();

// Verifica se é admin
$stmtUser = $conn->prepare("SELECT is_admin FROM usuarios WHERE id = :uid");
$stmtUser->execute([':uid' => $user_id]);
$user = $stmtUser->fetch();

if (!$user || $user['is_admin'] != 1) {
    // Se não for administrador, redireciona de volta para o dashboard
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
}

// Se for administrador, redireciona para a listagem de usuários do admin
header("Location: " . BASE_URL . "admin/users.php");
exit;
