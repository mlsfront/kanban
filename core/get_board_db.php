<?php
// core/get_board_db.php
require_once(__DIR__ . '/auth.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/logManager.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-cache, must-revalidate');

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do quadro não fornecido.']);
    exit;
}

$conn = get_db_connection();

try {
    // Verifica se é admin
    $stmtAdmin = $conn->prepare("SELECT is_admin FROM usuarios WHERE id = :uid");
    $stmtAdmin->execute([':uid' => $user_id]);
    $adminUser = $stmtAdmin->fetch();
    $isAdmin = ($adminUser && $adminUser['is_admin'] == 1);

    if ($isAdmin) {
        $stmt = $conn->prepare("SELECT data, version FROM boards WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } else {
        $stmt = $conn->prepare("SELECT data, version FROM boards WHERE id = :id AND usuario_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $user_id]);
    }

    if ($row = $stmt->fetch()) {
        header('X-Board-Version: ' . (int)$row['version']);
        // Retorna o JSON diretamente, pois já está no formato correto no BD.
        echo $row['data'];
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Quadro '$id' não encontrado no banco de dados."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno ao consultar o banco de dados.']);
    LogManager::error('Erro ao buscar quadro no MySQL', ['id' => $id, 'error' => $e->getMessage()]);
}
?>
