<?php
// login.php
require_once __DIR__ . '/../core/security.php';
ensure_secure_session();
require_once __DIR__ . '/../src/Config/App.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/config.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
}

$error = '';
$success = '';

if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $email = trim($_POST['email']);
    $password = $_POST['senha'];

    if (empty($email) || empty($password)) {
        $error = "Preencha todos os campos.";
    } else {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, senha_hash, status_email FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['senha_hash'])) {
            if ($user['status_email'] == 1) {
                // Prevenir Session Fixation
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $user['id'];
                header("Location: " . BASE_URL . "dashboard.php");
                exit;
            } else {
                $error = "E-mail não confirmado. Por favor, verifique sua caixa de entrada e confirme seu cadastro.";
            }
        } else {
            $error = "Credenciais inválidas.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kanban</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow-sm" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-4">Login</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error && str_contains($error, 'não confirmado')): ?>
            <form method="POST" action="<?= BASE_URL ?>auth/resend_confirmation.php" class="mb-3">
                <?= csrf_field() ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-outline-primary w-100" type="submit">Reenviar e-mail de confirmação</button>
            </form>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" id="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : 'admin@email.com' ?>" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-group">
                    <input type="password" name="senha" class="form-control" id="senha" value="123456" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="senha">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
        <div class="mt-3 text-center">
            <a href="register.php">Criar nova conta</a> | <a href="forgot_password.php">Esqueci a senha</a>
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
