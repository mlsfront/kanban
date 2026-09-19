-- 01_add_admin_and_logs.sql

-- Adiciona a coluna is_admin se ela não existir
SET @dbname = DATABASE();
SET @tablename = 'usuarios';
SET @columnname = 'is_admin';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE usuarios ADD COLUMN is_admin TINYINT(1) DEFAULT 0;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Cria a tabela de logs de acesso
CREATE TABLE IF NOT EXISTS `logs_acesso` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT DEFAULT NULL,
  `pagina` VARCHAR(255) NOT NULL,
  `metodo` VARCHAR(10) NOT NULL DEFAULT 'GET',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `data_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
