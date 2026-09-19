<?php
require_once __DIR__ . '/../core/security.php';
ensure_secure_session();
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../core/emailTemplates.php';
require_once __DIR__ . '/../src/Config/App.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . 'auth/login.php'); exit; }
require_valid_csrf();
$email = trim((string)($_POST['email'] ?? ''));
$conn = get_db_connection();
$stmt = $conn->prepare('SELECT id, nome, status_email FROM usuarios WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();
if ($user && !(int)$user['status_email']) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $update = $conn->prepare('UPDATE usuarios SET token_confirmacao = :token, expiracao_confirmacao = :expires WHERE id = :id');
    $update->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);
    $link = BASE_URL . 'auth/confirm.php?token=' . urlencode($token);
    MailerService::send($email, 'Confirme seu cadastro - ' . \App\Config\App::COMPANY_NAME_ADMIN, confirmation_email_body($user['nome'], $link));
}
$_SESSION['flash_success'] = 'Se o cadastro existir e ainda não estiver confirmado, um novo e-mail foi enviado.';
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
