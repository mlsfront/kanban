<?php
// register.php
require_once __DIR__ . '/../core/security.php';
ensure_secure_session();
require_once __DIR__ . '/../src/Config/App.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../core/emailTemplates.php';
require_once __DIR__ . '/../core/config.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $senha_confirm = $_POST['senha_confirm'];

    if (empty($nome) || empty($email) || empty($senha) || empty($senha_confirm)) {
        $error = "Preencha todos os campos.";
    } elseif ($senha !== $senha_confirm) {
        $error = "As senhas não coincidem.";
    } else {
        $conn = get_db_connection();
        
        // Verifica se o email já existe
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt_check->execute([':email' => $email]);
        if ($stmt_check->rowCount() > 0) {
            $error = "Este e-mail já está em uso.";
        } else {
            // Cria o hash da senha e token
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            $expiracaoConfirmacao = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha_hash, token_confirmacao, expiracao_confirmacao) VALUES (:nome, :email, :senha_hash, :token, :expiracao_confirmacao)");
            $result = $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha_hash' => $senha_hash,
                ':token' => $token,
                ':expiracao_confirmacao' => $expiracaoConfirmacao
            ]);

            if ($result) {
                // Envia e-mail de confirmação
                $link = BASE_URL . "auth/confirm.php?token=" . $token;
                $subject = "Confirme seu cadastro - " . \App\Config\App::COMPANY_NAME_ADMIN;
                $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h2 style='color: #0d6efd; margin: 0;'>Confirmação de Cadastro</h2>
                        <p style='color: #6c757d; font-size: 14px; margin-top: 5px;'>" . \App\Config\App::COMPANY_NAME_ADMIN . "</p>
                    </div>
                    <div style='line-height: 1.6; color: #333333;'>
                        <p>Olá, <strong>$nome</strong>!</p>
                        <p>Agradecemos por se registrar no nosso sistema Kanban. Para ativar a sua conta, confirme seu endereço de e-mail clicando no botão abaixo. O link é válido por <strong>24 horas</strong>.</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='$link' style='background-color: #0d6efd; color: #ffffff; text-decoration: none; padding: 12px 30px; font-weight: bold; border-radius: 5px; display: inline-block; box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);'>Confirmar E-mail</a>
                        </div>
                        <p style='font-size: 12px; color: #999999;'>Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:</p>
                        <p style='font-size: 12px; word-break: break-all;'><a href='$link' style='color: #0d6efd;'>$link</a></p>
                    </div>
                    <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 30px 0;'>
                    <div style='text-align: center; font-size: 12px; color: #999999;'>
                        <p>Esta é uma mensagem automática. Por favor, não responda a este e-mail.</p>
                    </div>
                </div>
                ";
                
                MailerService::send($email, $subject, $body);

                $success = "Conta criada com sucesso! Verifique seu e-mail (ou log local) para confirmar a conta.";
            } else {
                $error = "Erro ao criar a conta. Tente novamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Kanban</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow-sm" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-4">Criar Conta</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php else: ?>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="nome" class="form-label">Nome Completo</label>
                <input type="text" name="nome" class="form-control" id="nome" value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" id="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-group">
                    <input type="password" name="senha" class="form-control" id="senha" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="senha">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label for="senha_confirm" class="form-label">Confirmar Senha</label>
                <div class="input-group">
                    <input type="password" name="senha_confirm" class="form-control" id="senha_confirm" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="senha_confirm">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Registrar</button>
        </form>
        
        <?php endif; ?>
        <div class="mt-3 text-center">
            <a href="login.php">Já tem uma conta? Entrar</a>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
});
</script>
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script></body>
</html>
