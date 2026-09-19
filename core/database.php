<?php
// core/database.php
require_once __DIR__ . '/env.php';
date_default_timezone_set('America/Sao_Paulo');

function required_env(string $name, bool $allowEmpty = false): string
{
    $value = getenv($name);
    if ($value === false) {
        throw new RuntimeException("Variável de ambiente ausente: {$name}. Verifique o arquivo .env e reinicie o Apache.");
    }
    if (!$allowEmpty && trim($value) === '') {
        throw new RuntimeException("Variável de ambiente vazia: {$name}. Informe um valor no arquivo .env.");
    }
    return $value;
}

define('DB_SERVER', required_env('DB_SERVER'));
define('DB_USERNAME', required_env('DB_USERNAME'));
// Uma senha vazia só é aceita quando a variável existe explicitamente, cenário comum no XAMPP local.
define('DB_PASSWORD', required_env('DB_PASSWORD', true));
define('DB_NAME', required_env('DB_NAME'));

/**
 * Retorna uma conexão PDO segura com o banco de dados.
 * @return PDO
 */
function get_db_connection() {
    try {
        $dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em erros SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna arrays associativos por padrão
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Desativa emulação para segurança real de Prepared Statements
        ];
        
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Falha de conexão com o banco: ' . $e->getCode());
        http_response_code(500);
        die('Serviço temporariamente indisponível.');
    }
}
?>