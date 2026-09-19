<?php
// admin/boards.php
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

$target_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

// Busca os quadros (todos ou de um usuário específico)
$boards = [];
try {
    $sql = "
        SELECT b.id, b.title, b.last_updated, u.nome, u.email, b.usuario_id 
        FROM boards b 
        JOIN usuarios u ON b.usuario_id = u.id 
    ";
    
    if ($target_user_id) {
        $sql .= " WHERE b.usuario_id = :uid ";
    }
    
    $sql .= " ORDER BY b.last_updated DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($target_user_id) {
        $stmt->execute([':uid' => $target_user_id]);
    } else {
        $stmt->execute();
    }
    
    $boards = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Erro ao buscar quadros.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Quadros - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quadros <?= $target_user_id ? "do Usuário #$target_user_id" : "do Sistema" ?></h2>
        <div>
            <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-info me-2">Ver Usuários</a>
            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-secondary">Voltar ao Painel</a>
        </div>
    </div>
    
    <div class="card shadow-sm p-4">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID (Slug)</th>
                        <th>Título do Quadro</th>
                        <th>Dono</th>
                        <th>Última Atualização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($boards as $b): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($b['id']) ?></code></td>
                        <td><?= htmlspecialchars($b['title']) ?></td>
                        <td><?= htmlspecialchars($b['nome']) ?> <br><small class="text-muted"><?= htmlspecialchars($b['email']) ?></small></td>
                        <td><?= date('d/m/Y H:i:s', strtotime($b['last_updated'])) ?></td>
                        <td>
                            <!-- Admin pode acessar a página do quadro normalmente pois não tem restrição de usuário no arquivo de leitura do quadro, só no painel -->
                            <a href="<?= BASE_URL ?>board.php?id=<?= urlencode($b['id']) ?>" class="btn btn-sm btn-primary" target="_blank">Acessar Quadro</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($boards)): ?>
                    <tr><td colspan="5" class="text-center">Nenhum quadro encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script></body>
</html>
