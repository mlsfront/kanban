-- schema.sql (Nova estrutura completa do banco de dados)

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `senha_hash` VARCHAR(255) NOT NULL,
  `token_confirmacao` VARCHAR(255) DEFAULT NULL,
  `expiracao_confirmacao` DATETIME DEFAULT NULL,
  `token_recuperacao` VARCHAR(255) DEFAULT NULL,
  `expiracao_token` DATETIME DEFAULT NULL,
  `status_email` TINYINT(1) DEFAULT 0,
  `is_admin` TINYINT(1) DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `logs_acesso` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT DEFAULT NULL,
  `pagina` VARCHAR(255) NOT NULL,
  `metodo` VARCHAR(10) NOT NULL DEFAULT 'GET',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `data_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  INDEX `idx_logs_user_date` (`usuario_id`, `data_hora`),
  INDEX `idx_logs_date` (`data_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `boards` (
  `id` VARCHAR(50) NOT NULL,
  `usuario_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `data` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `version` INT UNSIGNED NOT NULL DEFAULT 1,
  -- ALTERAÇÃO AQUI: Chave primária agora engloba as duas colunas
  PRIMARY KEY (`id`, `usuario_id`),
  CONSTRAINT `fk_boards_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  INDEX `idx_boards_user_updated` (`usuario_id`, `last_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;