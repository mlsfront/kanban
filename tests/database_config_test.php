<?php
putenv('DB_SERVER=127.0.0.1');
putenv('DB_USERNAME=kanban_app');
putenv('DB_PASSWORD=');
putenv('DB_NAME=kanban');
require_once __DIR__ . '/../core/database.php';

if (DB_SERVER !== '127.0.0.1' || DB_USERNAME !== 'kanban_app' || DB_PASSWORD !== '' || DB_NAME !== 'kanban') {
    throw new RuntimeException('Configuração de banco não foi carregada corretamente.');
}
echo "database_config_test: ok\n";
