<?php
// core/migrate_to_mysql.php
// Executar somente pela CLI: php core/migrate_to_mysql.php <usuario_id>

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script deve ser executado pela CLI.\n");
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/boardValidator.php';

$usuarioId = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$usuarioId) {
    fwrite(STDERR, "Uso: php core/migrate_to_mysql.php <usuario_id>\n");
    exit(2);
}

$boardsPath = __DIR__ . '/../boards';
$boardPaths = array_filter(glob($boardsPath . '/*'), 'is_dir');
$conn = get_db_connection();
$conn->beginTransaction();
$count = 0;

try {
    $checkUser = $conn->prepare('SELECT id FROM usuarios WHERE id = :id');
    $checkUser->execute([':id' => $usuarioId]);
    if (!$checkUser->fetchColumn()) {
        throw new RuntimeException('Usuário destino não encontrado.');
    }

    $check = $conn->prepare('SELECT id FROM boards WHERE id = :id AND usuario_id = :uid');
    $insert = $conn->prepare('INSERT INTO boards (id, usuario_id, title, data, last_updated) VALUES (:id, :uid, :title, :data, NOW())');

    foreach ($boardPaths as $path) {
        $id = basename($path);
        if (!preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $id)) {
            fwrite(STDERR, "Ignorado ID inválido: {$id}\n");
            continue;
        }
        $jsonFile = $path . '/' . $id . '.json';
        if (!is_file($jsonFile)) {
            continue;
        }
        $raw = file_get_contents($jsonFile);
        $data = json_decode($raw);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON inválido em {$jsonFile}: " . json_last_error_msg());
        }
        [$valid, $error] = validate_board_document($data);
        if (!$valid) {
            throw new RuntimeException("Quadro {$id} inválido: {$error}");
        }

        $check->execute([':id' => $id, ':uid' => $usuarioId]);
        if ($check->fetchColumn()) {
            fwrite(STDOUT, "Já existente: {$id}\n");
            continue;
        }

        $insert->execute([
            ':id' => $id,
            ':uid' => $usuarioId,
            ':title' => (string)$data->title,
            ':data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
        $count++;
        fwrite(STDOUT, "Migrado: {$id}\n");
    }

    $conn->commit();
    fwrite(STDOUT, "Migração concluída. {$count} quadro(s) inserido(s).\n");
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    fwrite(STDERR, "Migração revertida: {$e->getMessage()}\n");
    exit(1);
}
