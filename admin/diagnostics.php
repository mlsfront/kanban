<?php
require_once(__DIR__ . '/../core/config.php');
require_once(__DIR__ . '/../core/database.php');
require_once(__DIR__ . '/../core/auth.php');

$conn = get_db_connection();
$admin = false;
$stmt = $conn->prepare('SELECT is_admin FROM usuarios WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $user_id]);
$admin = (int)$stmt->fetchColumn() === 1;
if (!$admin) {
    http_response_code(403);
    exit('Acesso negado.');
}

$checks = [];
$checks['php_version'] = PHP_VERSION;
$checks['base_url'] = BASE_URL;
$checks['endpoint_get_file'] = file_exists(__DIR__ . '/../core/get_board_db.php');
$checks['endpoint_save_file'] = file_exists(__DIR__ . '/../core/save_board.php');
$checks['board_entry_file'] = file_exists(__DIR__ . '/../board.php');
$checks['legacy_pages_count'] = count(glob(__DIR__ . '/../legacy/pages/*.php') ?: []);
$checks['legacy_json_count'] = count(glob(__DIR__ . '/../legacy/boards/*/*.json') ?: []);
try {
    $checks['boards_table'] = (bool)$conn->query("SELECT 1 FROM boards LIMIT 1")->fetchColumn();
    $versionMeta = $conn->query("SHOW COLUMNS FROM boards LIKE 'version'")->fetch();
    $ownerMeta = $conn->query("SHOW COLUMNS FROM boards LIKE 'usuario_id'")->fetch();
    $checks['version_column'] = (bool)$versionMeta;
    $checks['owner_column'] = (bool)$ownerMeta;
} catch (Throwable $e) {
    $checks['boards_table'] = false;
    $checks['version_column'] = false;
    $checks['database_error'] = $e->getMessage();
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diagnóstico do Kanban</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css">
<script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
</head>
<body><main class="container py-4">
<h1>Diagnóstico do Kanban</h1>
<p>Esta tela verifica o ambiente do servidor sem exibir senhas.</p>
<table class="table table-bordered"><thead><tr><th>Verificação</th><th>Resultado</th></tr></thead><tbody>
<?php foreach ($checks as $key => $value): ?>
<tr><td><code><?= htmlspecialchars((string)$key) ?></code></td><td><pre><?= htmlspecialchars(is_bool($value) ? ($value ? 'PASS' : 'FAIL') : (string)$value) ?></pre></td></tr>
<?php endforeach; ?>
</tbody></table>
<p><a href="<?= BASE_URL ?>dashboard.php">Voltar ao dashboard</a></p>
</main><script src="<?= BASE_URL ?>assets/js/theme.js"></script></body></html>
