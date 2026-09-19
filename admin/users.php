<?php
// admin/users.php
require_once(__DIR__ . '/../core/config.php');
require_once(__DIR__ . '/../core/auth.php');
require_once(__DIR__ . '/../core/database.php');

$conn = get_db_connection();

// Verifica se é admin
$stmtUser = $conn->prepare("SELECT is_admin FROM usuarios WHERE id = :uid");
$stmtUser->execute([':uid' => $user_id]);
$user = $stmtUser->fetch();

if (!$user || $user['is_admin'] != 1) {
    die("Acesso negado.");
}

// Ação de tornar admin
if (isset($_POST['make_admin']) && isset($_POST['user_id'])) {
    require_valid_csrf();
    $uid = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $stmt = $conn->prepare("UPDATE usuarios SET is_admin = 1 WHERE id = :id");
    $stmt->execute([':id' => $uid]);
}

// Busca usuários
$users = [];
try {
    $stmt = $conn->query("
        SELECT u.id, u.nome, u.email, u.criado_em, u.is_admin, u.status_email, 
               (SELECT COUNT(*) FROM boards b WHERE b.usuario_id = u.id) as total_boards
        FROM usuarios u
        ORDER BY u.id DESC
    ");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Erro ao buscar usuários.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h2>Gerenciar Usuários</h2>
        <div class="d-flex">
            <a href="<?= BASE_URL ?>admin/logs.php" class="btn btn-outline-info me-2">Ver Logs</a>
            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-secondary">Voltar ao Painel</a>
        </div>
    </div>
    
    <div class="card shadow-sm p-4">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Criado Em</th>
                        <th>Quadros</th>
                        <th>Status Email</th>
                        <th>Admin?</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['nome']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
                        <td><span class="badge bg-secondary"><?= $u['total_boards'] ?></span></td>
                        <td>
                            <?php if ($u['status_email']): ?>
                                <span class="badge bg-success">Verificado</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['is_admin']): ?>
                                <span class="badge bg-primary">Sim</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$u['is_admin']): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Tornar este usuário admin?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" name="make_admin" class="btn btn-sm btn-success">Promover Admin</button>
                            </form>
                            <?php endif; ?>
                            <!-- Pode acessar os quadros do usuário pelo admin/boards.php -->
                            <a href="<?= BASE_URL ?>admin/boards.php?user_id=<?= $u['id'] ?>" class="btn btn-sm btn-info">Ver Quadros</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script></body>
</html>
