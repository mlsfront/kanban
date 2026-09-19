<?php
require_once __DIR__ . '/../core/env.php';

$path = sys_get_temp_dir() . '/kanban-env-' . bin2hex(random_bytes(4));
file_put_contents($path, "TEST_ENV_NAME=kanban\nTEST_ENV_QUOTED=\"valor com espaços\"\nTEST_ENV_COMMENT=value # comentário\n");
load_env_file($path);
if (getenv('TEST_ENV_NAME') !== 'kanban') throw new RuntimeException('Variável dotenv não carregada.');
if (getenv('TEST_ENV_QUOTED') !== 'valor com espaços') throw new RuntimeException('Valor com aspas não interpretado.');
if (getenv('TEST_ENV_COMMENT') !== 'value') throw new RuntimeException('Comentário dotenv não removido.');
@unlink($path);
echo "env_test: ok\n";
