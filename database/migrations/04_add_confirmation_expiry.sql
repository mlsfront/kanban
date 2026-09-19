ALTER TABLE usuarios
    ADD COLUMN expiracao_confirmacao DATETIME NULL AFTER token_confirmacao;
