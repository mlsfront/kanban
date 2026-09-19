<?php
// forgot_password.php
require_once __DIR__ . '/../core/security.php';
ensure_secure_session();
require_once __DIR__ . '/../src/Config/App.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../core/emailTemplates.php';
require_once __DIR__ . '/../core/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Preencha o e-mail.";
    } else {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, nome, status_email FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && !(int)$user['status_email']) {
            $token = bin2hex(random_bytes(32));
            $expiracaoConfirmacao = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $update = $conn->prepare("UPDATE usuarios SET token_confirmacao = :token, expiracao_confirmacao = :expiracao WHERE id = :id");
            $update->execute([':token' => $token, ':expiracao' => $expiracaoConfirmacao, ':id' => $user['id']]);
            $link = BASE_URL . "auth/confirm.php?token=" . urlencode($token);
            MailerService::send($email, "Confirme seu cadastro - " . \App\Config\App::COMPANY_NAME_ADMIN, confirmation_email_body($user['nome'], $link));
        } elseif ($user) {
            $token = bin2hex(random_bytes(32));
            // Expira em 1 hora
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $conn->prepare("UPDATE usuarios SET token_recuperacao = :token, expiracao_token = :expiracao WHERE id = :id");
            $update->execute([
                ':token' => $token,
                ':expiracao' => $expiracao,
                ':id' => $user['id']
            ]);

            $link = BASE_URL . "auth/reset_password.php?token=" . $token;
            $subject = "Recuperação de Senha - " . \App\Config\App::COMPANY_NAME_ADMIN;
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='color: #0d6efd; margin: 0;'>Recuperação de Senha</h2>
                    <p style='color: #6c757d; font-size: 14px; margin-top: 5px;'>" . \App\Config\App::COMPANY_NAME_ADMIN . "</p>
                </div>
                <div style='line-height: 1.6; color: #333333;'>
                    <p>Olá,</p>
                    <p>Você solicitou a redefinição da sua senha de acesso no nosso sistema Kanban.</p>
                    <p>Clique no botão abaixo para criar uma nova senha. Este link é válido por <strong>1 hora</strong>.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$link' style='background-color: #0d6efd; color: #ffffff; text-decoration: none; padding: 12px 30px; font-weight: bold; border-radius: 5px; display: inline-block; box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);'>Redefinir Senha</a>
                    </div>
                    <p style='font-size: 12px; color: #999999;'>Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:</p>
                    <p style='font-size: 12px; word-break: break-all;'><a href='$link' style='color: #0d6efd;'>$link</a></p>
                </div>
                <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 30px 0;'>
                <div style='text-align: center; font-size: 12px; color: #999999;'>
                    <p>Se você não solicitou esta alteração, pode ignorar este e-mail.</p>
                </div>
            </div>
            ";
            
            MailerService::send($email, $subject, $body);
        }
        
        // Sempre mostramos a mesma mensagem para não revelar quais e-mails estão cadastrados
        $success = "Se o e-mail existir em nossa base, as instruções de recuperação foram enviadas.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow-sm" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-4">Recuperar Senha</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php else: ?>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail Cadastrado</label>
                <input type="email" name="email" class="form-control" id="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Enviar link de recuperação</button>
        </form>
        
        <?php endif; ?>
        <div class="mt-3 text-center">
            <a href="login.php">Voltar ao Login</a>
        </div>
    </div>
</div>
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script></body>
</html>
