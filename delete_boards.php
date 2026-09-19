<?php
// Sanear o ID (copiada de new_board.php)
function safe_sanitize_id($id) {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
}

require_once(__DIR__ . '/core/config.php');
require_once(__DIR__ . '/core/auth.php');
require_once(__DIR__ . '/core/database.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['board_ids'])) {
    header("Location: " . BASE_URL . "dashboard.php?error=no_boards_selected");
    exit;
}
require_valid_csrf();

$board_ids = $_POST['board_ids'];
$deleted_count = 0;

$conn = get_db_connection();
$stmt = $conn->prepare("DELETE FROM boards WHERE id = :id AND usuario_id = :uid");

foreach ($board_ids as $raw_id) {
    $id = safe_sanitize_id($raw_id);
    if (empty($id) || $id === '..' || $id === '.') {
        continue;
    }

    // A persistência oficial é o banco; artefatos em legacy/ não participam do fluxo ativo.
    // Excluir somente o quadro pertencente ao usuário autenticado.
    if ($stmt) {
        $stmt->execute([':id' => $id, ':uid' => $user_id]);
    }

    $deleted_count++;
}

// Redireciona para a página inicial com uma mensagem de sucesso (ou apenas sucesso)
header("Location: " . BASE_URL . "dashboard.php?status=deleted&count=$deleted_count");
?>