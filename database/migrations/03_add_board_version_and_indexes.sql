-- Adiciona controle otimista e índices da operação.
SET @db = DATABASE();

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'boards' AND COLUMN_NAME = 'version') = 0,
  'ALTER TABLE boards ADD COLUMN version INT UNSIGNED NOT NULL DEFAULT 1 AFTER last_updated',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'boards' AND INDEX_NAME = 'idx_boards_user_updated') = 0,
  'CREATE INDEX idx_boards_user_updated ON boards (usuario_id, last_updated)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'logs_acesso' AND INDEX_NAME = 'idx_logs_user_date') = 0,
  'CREATE INDEX idx_logs_user_date ON logs_acesso (usuario_id, data_hora)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
