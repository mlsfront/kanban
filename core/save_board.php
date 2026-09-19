<?php
// core/save_board.php (Versão para SALVAR no MYSQL)
require_once(__DIR__ . '/auth.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/logManager.php');
require_once(__DIR__ . '/boardValidator.php');

header('Content-Type: application/json; charset=utf-8');
$response = ['success' => false, 'message' => 'Erro desconhecido.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents('php://input');
    if (strlen($data) > 2 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'Payload excede o limite permitido.']);
        exit;
    }
    $data_object = json_decode($data); 

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'JSON inválido recebido.';
        LogManager::error('JSON inválido ao salvar quadro', ['error' => json_last_error_msg(), 'data' => substr($data, 0, 100)]);
    } else if (empty($data_object->board_id) || !isset($data_object->data)) { 
        $response['message'] = 'ID ou dados do quadro ausentes.';
        LogManager::warning('ID do quadro ausente ao salvar', ['data' => print_r($data_object, true)]);
    } else {
        $board_id = (string)$data_object->board_id;
        $board_data = $data_object->data;
        if (!preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $board_id)) {
            http_response_code(422);
            $response['message'] = 'ID do quadro inválido.';
            echo json_encode($response);
            exit;
        }
        [$validBoard, $validationError] = validate_board_document($board_data);
        if (!$validBoard) {
            http_response_code(422);
            $response['message'] = $validationError;
            echo json_encode($response);
            exit;
        }
        $board_data->user_id = (int)$user_id;
        $board_data->owner_id = (int)$user_id;
        $board_title = (string)$board_data->title;
        $expectedVersion = filter_var($data_object->version ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$expectedVersion) {
            http_response_code(422);
            $response['message'] = 'Versão do quadro inválida.';
            echo json_encode($response);
            exit;
        }
        $sanitized_id = $board_id;
        $json_data_str = json_encode($board_data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $conn = get_db_connection();
        
        try {
            // Verifica se o board já existe E pertence ao usuário
            $stmt_check = $conn->prepare("SELECT id, version FROM boards WHERE id = :id AND usuario_id = :uid");
            $stmt_check->execute([':id' => $sanitized_id, ':uid' => $user_id]);
            $existing = $stmt_check->fetch();
            $newVersion = 1;

            if ($existing) {
                $stmt = $conn->prepare("UPDATE boards SET title = :title, data = :data, version = version + 1, last_updated = NOW() WHERE id = :id AND usuario_id = :uid AND version = :version");
                $stmt->execute([
                    ':title' => $board_title,
                    ':data' => $json_data_str,
                    ':id' => $sanitized_id,
                    ':uid' => $user_id,
                    ':version' => $expectedVersion,
                ]);
                if ($stmt->rowCount() !== 1) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Conflito de versão. Recarregue o quadro antes de salvar.']);
                    exit;
                }
                $newVersion = $expectedVersion + 1;
            } else {
                $stmt = $conn->prepare("INSERT INTO boards (id, usuario_id, title, data, version, last_updated) VALUES (:id, :uid, :title, :data, 1, NOW())");
                $stmt->execute([
                    ':id' => $sanitized_id,
                    ':uid' => $user_id,
                    ':title' => $board_title,
                    ':data' => $json_data_str
                ]);
            }

            $response['success'] = true;
            $response['version'] = $newVersion;
            $response['message'] = "Dados salvos com sucesso no Banco de Dados.";
            

        } catch (Exception $e) {
            $response['message'] = "Exceção do Banco de Dados.";
            LogManager::error('Exceção ao salvar quadro', ['board_id' => $sanitized_id, 'error' => $e->getMessage()]);
        }
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);
exit;
?>