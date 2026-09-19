-- 1. Remove a restrição de chave estrangeira antiga para não travar a alteração da PK
ALTER TABLE `boards` DROP FOREIGN KEY `fk_boards_usuario`;

-- 2. Remove a chave primária antiga (que era apenas o 'id')
ALTER TABLE `boards` DROP PRIMARY KEY;

-- 3. Adiciona a nova chave primária composta (id + usuario_id)
ALTER TABLE `boards` ADD PRIMARY KEY (`id`, `usuario_id`);

-- 4. Restaura a chave estrangeira ligando o usuario_id à tabela de usuarios
ALTER TABLE `boards` ADD CONSTRAINT `fk_boards_usuario` 
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;