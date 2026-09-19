<?php
// Função para remover um diretório e seu conteúdo (necessário para delete_boards.php)
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                    rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
        }
        rmdir($dir);
    }
}

// Compatibilidade com chamadas antigas; a sanitização agora translitera acentos.
function safe_sanitize_id($id) {
    return sanitize_board_id((string)$id);
}

require_once(__DIR__ . '/core/config.php');
require_once(__DIR__ . '/core/auth.php');
require_once(__DIR__ . '/core/database.php');
require_once(__DIR__ . '/core/boardIdentity.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['board_id']) || empty($_POST['template'])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
}
require_valid_csrf();

$requestedName = trim((string)$_POST['board_id']);
$id = safe_sanitize_id($requestedName);
$template = safe_sanitize_id($_POST['template']);

if (empty($id) || $id === '..' || $id === '.') {
    die("ID do quadro inválido.");
}

$conn = get_db_connection();

try {
    // ALTERAÇÃO AQUI: Verifica se o quadro já existe PARA ESTE USUÁRIO específico
    $stmt_check = $conn->prepare("SELECT id FROM boards WHERE id = :id AND usuario_id = :uid");
    $stmt_check->execute([
        ':id' => $id,
        ':uid' => $user_id // Garanta que a variável $user_id da sua sessão esteja correta aqui
    ]);
    
    if ($stmt_check->rowCount() > 0) {
        // Redireciona informando que o usuário já possui esse quadro
        header("Location: " . BASE_URL . "dashboard.php?error=exists");
        exit;
    }

    $templateFile = __DIR__ . "/templates/$template.json";
    $board_title = $requestedName !== '' ? $requestedName : ucfirst(str_replace('_',' ', $id));
    $jsonData = [];

    if (!file_exists($templateFile)) {
        $jsonData = [
            "title" => $board_title,
            "columns" => [
                ["id" => "backlog", "title" => "Backlog", "color" => "#0d6efd"],
                ["id" => "desenvolvimento", "title" => "Em Desenvolvimento", "color" => "#ffc107"],
                ["id" => "testes", "title" => "Testes", "color" => "#198754"],
                ["id" => "concluido", "title" => "Concluído", "color" => "#6c757d"]
            ],
            "tasks" => [
                "backlog" => [],
                "desenvolvimento" => [],
                "testes" => [],
                "concluido" => []
            ]
        ];
    } else {
        $templateContent = file_get_contents($templateFile);
        $jsonData = json_decode($templateContent, true);
        $jsonData['title'] = $board_title;
    }

    $jsonData['user_id'] = (int)$user_id;
    $jsonData['owner_id'] = (int)$user_id;
    $json_data_str = json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    // O MySQL é a fonte única de verdade; não criamos JSON redundante no filesystem.
    // Insere no banco de dados com usuario_id
    $stmt = $conn->prepare("INSERT INTO boards (id, usuario_id, title, data, last_updated) VALUES (:id, :uid, :title, :data, NOW())");
    $result = $stmt->execute([
        ':id' => $id,
        ':uid' => $user_id,
        ':title' => $board_title,
        ':data' => $json_data_str
    ]);
    
    if (!$result) {
        die("Erro ao salvar no banco de dados.");
    }

    header("Location: " . BASE_URL . "dashboard.php");
    exit;

} catch (Exception $e) {
    die("Erro interno: " . $e->getMessage());
}

