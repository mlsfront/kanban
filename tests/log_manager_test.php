<?php
$path = sys_get_temp_dir() . '/kanban-log-test-' . bin2hex(random_bytes(4));
putenv('APP_LOG_PATH=' . $path);
require_once __DIR__ . '/../core/logManager.php';
LogManager::info('teste', ['request_id' => 'abc']);
$log = $path . '/debug.log';
if (!is_file($log)) {
    throw new RuntimeException('Arquivo de log não foi criado no caminho configurado.');
}
$content = file_get_contents($log);
if (strpos($content, 'teste') === false) {
    throw new RuntimeException('Entrada de log ausente.');
}
@unlink($log);
@rmdir($path . '/archive');
@rmdir($path);
echo "log_manager_test: ok\n";
