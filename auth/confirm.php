<?php
// confirm.php
session_start();
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/config.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Token não fornecido.");
}

$token = $_GET['token'];
$conn = get_db_connection();

$stmt = $conn->prepare("SELECT id, status_email, expiracao_confirmacao FROM usuarios WHERE token_confirmacao = :token LIMIT 1");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if ($user) {
    if ($user['status_email'] == 1) {
        $msg = "Seu e-mail já foi confirmado. Você já pode fazer login.";
    } elseif (empty($user['expiracao_confirmacao']) || strtotime($user['expiracao_confirmacao']) < time()) {
        $msg = "Token inválido ou expirado. Solicite um novo e-mail de confirmação.";
    } else {
        $update = $conn->prepare("UPDATE usuarios SET status_email = 1, token_confirmacao = NULL, expiracao_confirmacao = NULL WHERE id = :id");
        $update->execute([':id' => $user['id']]);
        
        $_SESSION['flash_success'] = "E-mail confirmado com sucesso! Você já pode fazer login.";
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }
} else {
    $msg = "Token inválido ou expirado.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmação de E-mail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body class="bg-light">
<div class="container mt-5">
    <div class="alert alert-info">
        <?= htmlspecialchars($msg) ?>
    </div>
    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary">Ir para o Login</a>
</div>
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script></body>
</html>
