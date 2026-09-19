<?php
// reset_password.php
require_once __DIR__ . '/../core/security.php';
ensure_secure_session();
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/config.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Token inválido.");
}

$token = $_GET['token'];
$conn = get_db_connection();

$stmt = $conn->prepare("SELECT id, expiracao_token FROM usuarios WHERE token_recuperacao = :token LIMIT 1");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    die("Token inválido ou expirado.");
}

// Verifica se expirou
if (strtotime($user['expiracao_token']) < time()) {
    die("Este token de recuperação expirou. Por favor, solicite um novo.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $senha = $_POST['senha'];
    $senha_confirm = $_POST['senha_confirm'];

    if (empty($senha) || empty($senha_confirm)) {
        $error = "Preencha ambas as senhas.";
    } elseif ($senha !== $senha_confirm) {
        $error = "As senhas não coincidem.";
    } else {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $update = $conn->prepare("UPDATE usuarios SET senha_hash = :hash, token_recuperacao = NULL, expiracao_token = NULL WHERE id = :id");
        if ($update->execute([':hash' => $senha_hash, ':id' => $user['id']])) {
            $_SESSION['flash_success'] = "Senha alterada com sucesso! Você já pode fazer login com a nova senha.";
            header("Location: " . BASE_URL . "auth/login.php");
            exit;
        } else {
            $error = "Erro ao alterar a senha. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow-sm" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-4">Redefinir Senha</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="senha" class="form-label">Nova Senha</label>
                <div class="input-group">
                    <input type="password" name="senha" class="form-control" id="senha" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="senha">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label for="senha_confirm" class="form-label">Confirmar Nova Senha</label>
                <div class="input-group">
                    <input type="password" name="senha_confirm" class="form-control" id="senha_confirm" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="senha_confirm">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Alterar Senha</button>
        </form>
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
