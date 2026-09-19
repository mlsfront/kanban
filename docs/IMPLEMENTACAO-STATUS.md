# Status de implementação e liberação

**Data do registro:** 15 de agosto de 2026. **Coordenação:** MLSFront. **Estado geral:** em execução; não liberado para produção.

## 1. Frentes e commits

| Frente | Branch | Commit principal | Estado | Evidência |
|---|---|---|---|---|
| Segurança P0 | `security/p0-hardening` | `3252f5c` e `1909644` | Implementada | CSRF, sessão, logout POST, escaping, configuração externa e teste CSRF |
| Integridade e persistência | `fix/data-integrity` | `5187680` e `a3fe255` | Implementada em código | Validador JSON, migração PDO, ownership, índices e controle otimista de versão |
| Release e operação | `test/release-gates` | `b5c613e` | Implementada em código | Headers Apache, logs fora do webroot e testes operacionais |

As branches são deliberadamente separadas. A integração deve ocorrer por pull requests revisadas, começando por `security/p0-hardening`, seguida de `fix/data-integrity` e, por fim, `test/release-gates`.

## 2. Decisões registradas

A origem pública não é mais derivada de `HTTP_HOST`; ela deve ser fornecida por `APP_URL`. As credenciais de banco são obtidas por variáveis de ambiente, e o fallback local deve ser substituído por um usuário MySQL/MariaDB sem privilégios administrativos antes do staging.

Todos os POSTs mutáveis identificados passaram a exigir token CSRF. O logout deixou de ser uma operação GET e passou a exigir POST. O documento Kanban agora é validado no servidor, com limites de payload, títulos, tarefas, prioridades, colunas e IDs. O autosave passou a transportar uma versão e o servidor rejeita sobrescritas com HTTP 409.

Os logs de debug e e-mail não devem conter corpos de mensagens, tokens ou credenciais. O caminho é configurável por `APP_LOG_PATH` e `MAIL_LOG_PATH`, com fallback no diretório temporário do sistema apenas para desenvolvimento. Em produção, esses caminhos devem apontar para armazenamento fora do document root e com permissões restritivas.

## 3. Validações executadas

| Validação | Resultado |
|---|---|
| `php tests/security_test.php` | Aprovado |
| `php tests/board_validator_test.php` | Aprovado |
| `php tests/log_manager_test.php` | Aprovado |
| Lint de todos os arquivos PHP | Aprovado |
| `node --check` nos módulos JavaScript | Aprovado |
| Teste manual de banco, SMTP, navegador e Apache | Pendente de staging |

## 4. Gates obrigatórios antes de produção

A migração `database/migrations/03_add_board_version_and_indexes.sql` deve ser executada em uma cópia restaurada do banco, com backup verificável e plano de rollback. O staging deve testar cadastro, confirmação de e-mail, login, recuperação de senha, criação/edição/exclusão de quadros, promoção administrativa, limpeza de logs, importação inválida e conflito simultâneo entre dois clientes.

A equipe deve configurar `APP_URL`, `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`, `APP_LOG_PATH` e `MAIL_LOG_PATH`, remover valores locais, configurar HTTPS, validar permissões e confirmar que `mail.log`, `debug.log`, arquivos SQL e backups não podem ser baixados via HTTP.

A liberação permanece **No-Go** até que o staging confirme a migração, os testes de autorização por usuário, o conflito HTTP 409, o comportamento do mailer, as regras Apache e a ausência de vulnerabilidades P0 conhecidas. O aceite final exige um responsável de produto, um responsável técnico e um revisor de segurança.


## 5. Correção do carregamento dotenv

O erro `SQLSTATE[3D000]: 1046 No database selected` ocorreu porque `getenv()` não lê automaticamente um arquivo `.env`. O Apache iniciou o PHP sem `DB_NAME`, e o DSN foi montado sem a parte `dbname`, independentemente de o arquivo existir no document root.

Foi adicionado `core/env.php`, um carregador dotenv explícito que lê `kanban/.env` e preserva a precedência de variáveis já definidas pelo processo Apache. `core/database.php` agora carrega esse helper e exige `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD` e `DB_NAME`; não existe mais fallback hardcoded para o banco. `core/config.php` aplica a mesma regra a `APP_URL`.

No XAMPP, o arquivo deve estar exatamente em `/opt/lampp/htdocs/kanban/.env`, com permissões de leitura para o usuário do Apache. Depois de alterar o arquivo, reinicie o Apache para eliminar processos PHP antigos. O teste isolado `php tests/env_test.php` foi aprovado; a conexão PDO real ainda depende de o MySQL/MariaDB estar ativo, do banco existir e das credenciais do `.env` serem válidas.


## 6. Ajuste específico para DB_PASSWORD no XAMPP

O erro reportado como `Variável de ambiente obrigatória ausente: DB_PASSWORD` pode ocorrer quando o `.env` contém `DB_PASSWORD=` vazio. Essa situação é diferente de uma variável ausente: no XAMPP local, o usuário de desenvolvimento pode ter senha vazia, embora isso não seja recomendado para produção.

A validação foi ajustada para aceitar `DB_PASSWORD=` somente quando a variável existe explicitamente. `DB_NAME`, `DB_SERVER`, `DB_USERNAME` e `APP_URL` continuam exigindo valores não vazios. O teste `database_config_test.php` comprova esse comportamento. Em produção, configure uma senha real para `DB_PASSWORD` e utilize um usuário de banco com privilégios mínimos.


## 7. Correção de conflito após importação e exclusão

O JSON exportado não contém `_version`, porque esse metadado é mantido como propriedade não enumerável do objeto carregado do servidor. A importação substituía `Kanban.data` por um objeto novo sem essa propriedade; o autosave então enviava `version: 1`, enquanto o banco já estava em uma versão maior. O servidor respondia 409, a interface permanecia alterada apenas em memória e o recarregamento restaurava o banco.

A correção consulta `get_board_db.php` imediatamente antes da importação, obtém o header `X-Board-Version` e atribui essa versão ao documento importado. Também foi adicionada uma fila de autosave para impedir requisições concorrentes e preservar alterações feitas durante uma gravação. O estado `_version` continua sendo atualizado a partir da resposta do servidor após cada salvamento.

Após instalar a correção, faça uma recarga completa uma vez, importe o JSON, aguarde o indicador de salvamento concluído e então teste a exclusão. Um 409 real continua significando alteração externa concorrente; nesse caso, a interface não repete o envio automaticamente nem sobrescreve o estado remoto.


## 8. Melhorias funcionais, visual e operacionais

A branch `feature/requested-improvements` implementa a conclusão visual de tarefas, sanitização transliterada de nomes, criação do JSON físico inicial, ownership no documento do quadro, tema global e configuração de e-mail/logs. Nomes como `Controle Produção` passam a gerar o identificador `ControleProducao`, enquanto o título salvo preserva a grafia original.

Ao mover uma tarefa para uma coluna cujo ID ou título representa conclusão, o sistema marca `completed`, limpa `due_date`/`dueDate`, oculta indicadores de vencimento e aplica a cor verde. O JSON inicial é gravado em `boards/<id>/<id>.json`, e os campos `user_id` e `owner_id` são preenchidos pelo usuário autenticado tanto na criação quanto no salvamento.

O tema global usa `assets/css/theme.css` e `assets/js/theme.js`, com preferência persistida em `localStorage` e suporte a tema claro/escuro nas páginas PHP. O e-mail agora aceita `MAIL_DRIVER=log`, `smtp` ou `off`, além de `MAIL_ENABLED`; o modo `log` grava metadados em `logs/mail.log` sem persistir o corpo ou tokens. O logger de acesso também complementa o banco com `logs/debug.log`, usando rotação e bloqueio HTTP da pasta `logs/`.

Os testes `requested_features_test`, lint PHP e checagem JavaScript foram aprovados. A validação manual ainda deve confirmar no XAMPP a aparência de todas as telas, envio SMTP real, permissões da pasta `logs/`, criação física do JSON e isolamento de quadros entre dois usuários.


## 9. Correções de e-mail, confirmação e tema

A branch atual também corrige escapes literais `\\n` que apareciam antes de `</head>` e `</body>`. O driver `log` passou a gravar mensagens no formato legível com `Date`, `To`, `Subject` e `Body`, usando quebras reais de linha. Administradores podem abrir `admin/mail_logs.php` para visualizar o corpo HTML em um iframe sandboxed, com botões e links clicáveis.

Tokens de confirmação agora têm expiração de 24 horas, enquanto tokens de redefinição continuam expirando em 1 hora. O login bloqueia contas pendentes com a mensagem padronizada e oferece reenvio. O endpoint de reenvio gera novo token; o fluxo de recuperação, quando encontra conta não confirmada, reenvia confirmação em vez de emitir reset. O reset de senha só é enviado para e-mails confirmados.

Os templates de confirmação e redefinição usam HTML/CSS responsivo, botão principal e URL visível. O tema global exibe somente o ícone de lua/sol no canto superior direito, evita duplicidade de controles e aplica variáveis e regras `!important` a body, cards, tabelas, formulários, modais e elementos Bootstrap.


## 10. Fonte única de dados, página dinâmica e tema sem flicker

A persistência oficial dos quadros passa a ser exclusivamente o MySQL. Novos quadros não criam diretórios nem arquivos JSON em `boards/`, e o salvamento não atualiza backups físicos. Arquivos JSON antigos podem permanecer como artefatos de migração, mas não participam mais do fluxo normal.

A geração de um PHP por quadro também foi interrompida. O ponto de entrada passa a ser `board.php?id=<id>`, que valida a sessão, verifica o ownership no banco e carrega os dados pelo endpoint autorizado. Links do dashboard e do painel administrativo foram atualizados. Páginas legadas em `pages/` podem ser removidas após validar todos os links externos e aplicar uma rotina de limpeza controlada.

Para eliminar o flash do tema claro, `assets/js/theme-init.js` é carregado sincronicamente no `<head>` e aplica `data-theme` antes da pintura. `theme.js` continua responsável pelo botão e pela persistência. O CSS escuro foi ampliado para acordeões, quadros Kanban, tabelas, cards, formulários, modais e elementos Bootstrap.


## 11. Correção da página dinâmica do quadro

A página `board.php` estava carregando o cabeçalho PHP e o acesso ao banco corretamente, mas os imports ES dos módulos ainda usavam caminhos relativos de `pages/*.php` (`../core/...`). Como `board.php` fica na raiz do projeto, o navegador não encontrava esses módulos; por isso o elemento `#kanban` permanecia vazio e somente o cabeçalho era exibido.

Os imports foram corrigidos para `./core/...`. O bootstrap PHP e todos os módulos JavaScript passaram pelas validações sintáticas. A persistência continua exclusivamente no MySQL; nenhum `pages/*.php` ou JSON físico é necessário para renderizar o conteúdo do quadro.


## 12. Correção de autosave e importação na página dinâmica

A página `board.php` estava sendo servida pela raiz, mas os módulos ainda chamavam `../core/save_board.php` e `../core/get_board_db.php`, caminhos relativos que apontavam para fora da aplicação. O navegador reportava falha de conexão ao criar tarefas e a importação não conseguia sincronizar a versão do quadro.

Os endpoints foram ajustados para `./core/save_board.php` e `./core/get_board_db.php`, incluindo o pedido de versão antes da importação. A leitura, o autosave e a importação continuam usando o banco como fonte única.


## 13. Correção final da persistência no board dinâmico

A correção anterior ajustou os caminhos em `board.php`, mas os módulos ES são carregados a partir da pasta `core`. Dentro de `core/kanban.js`, `core/storage.js` e `core/boardManager.js`, o caminho `./core/...` era resolvido pelo navegador como `/kanban/core/core/...`, causando a falha de conexão e o retorno ao estado anterior após recarregar.

Os módulos agora usam `./save_board.php` e `./get_board_db.php`, que resolvem corretamente para os endpoints dentro da própria pasta `core`. A leitura, o autosave e a sincronização de versão da importação passam a atingir o servidor real.


## 14. Auditoria de migração e diagnóstico operacional

### Causa confirmada do erro atual

O navegador resolve URLs passadas a `fetch()` com base na URL do documento, e não com base no diretório físico do módulo ES. Quando os módulos usavam `./save_board.php` e `./get_board_db.php`, as chamadas foram resolvidas para `/kanban/save_board.php` e `/kanban/get_board_db.php`, que não existem. O log do Apache confirmou os HTTP 404. A solução definitiva é injetar `API_GET_BOARD` e `API_SAVE_BOARD` em `board.php` usando `BASE_URL`, e fazer `Storage`, `Board` e `Kanban` consumirem `config.source` e `config.saveUrl`.

### Loop de autosave

O autosave mantinha `changesPending = true` depois de uma falha de rede ou de uma resposta inválida e o bloco `finally` agendava outra tentativa. Isso produzia chamadas POST infinitas. Agora uma falha bloqueia novas tentativas automáticas, registra no console a URL, o status e os primeiros 500 caracteres da resposta e libera nova tentativa apenas quando o usuário fizer uma nova alteração. Respostas não JSON também exibem o status HTTP em vez de mascararem todo erro como “Falha na conexão”.

### Diagnóstico controlado

Foi criada a página administrativa `admin/diagnostics.php`, que não expõe senhas e verifica versão PHP, `BASE_URL`, existência física dos endpoints, `board.php`, quantidade de artefatos legados, tabela `boards`, coluna `version` e coluna `usuario_id`. O relatório estático reprodutível fica em `static-reference-audit.txt` e pode ser regenerado por `tools/audit_references.sh`.

### Funções órfãs, duplicidades e redundâncias identificadas

| Achado | Evidência | Decisão |
|---|---|---|
| Bloco legado de geração de páginas | `new_board.php` redireciona e encerra na linha 103; o bloco de geração de `pages/*.php` segue até a linha 296 | Manter temporariamente para remoção controlada; não participa do fluxo ativo |
| Sanitização duplicada | `safe_sanitize_id` aparece em `dashboard.php`, `new_board.php` e `delete_boards.php`; existe também `core/boardIdentity.php` | Centralizar em próxima refatoração, após validar compatibilidade de slugs |
| Remoção recursiva duplicada | `rrmdir` aparece em `new_board.php` e `delete_boards.php` | Extrair para helper comum antes de remover os artefatos legados |
| Helpers sem chamadas detectáveis | `configured_app_url`, `password_reset_email_body` e `redirect` têm somente declaração no scan estático | Candidatos a remoção ou integração; não excluir automaticamente sem revisar fluxos de e-mail e redirecionamento |
| JSON e PHP legados | Ainda há artefatos em `pages/` e `boards/` | Não são fonte de verdade; remover somente após backup e verificação de links externos |

O resultado completo do scan deve ser usado como checklist de refatoração, não como prova isolada de que uma função é inútil: chamadas dinâmicas, includes indiretos e uso em templates precisam de revisão antes da exclusão.


## 15. Correção do remetente SMTP no Mailtrap

O erro `Invalid address: (From): noreply@localhost` foi causado pelo domínio local inválido para o fluxo SMTP. A configuração `src/Config/Mail.php` deixou de usar esse fallback e agora retorna vazio quando `MAIL_FROM_ADDRESS` não está definido. Antes de chamar o PHPMailer, `core/mailer.php` valida o endereço com `FILTER_VALIDATE_EMAIL` e interrompe o envio com uma mensagem operacional clara quando o valor está ausente ou inválido.

O `.env.example` agora recomenda `MAIL_FROM_ADDRESS=noreply@example.com`. No ambiente XAMPP, o arquivo `.env` deve conter um endereço com domínio válido, por exemplo:

```dotenv
MAIL_ENABLED=1
MAIL_DRIVER=smtp
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME=KanbanApp
```

O teste `tests/mail_config_test.php` confirma que um endereço com domínio é aceito e que `noreply@localhost` é rejeitado. Também foram aprovados os lints de `src/Config/Mail.php` e `core/mailer.php`. O envio SMTP real permanece dependente da execução no XAMPP com as credenciais atuais do Mailtrap e não foi realizado neste sandbox.

As credenciais Mailtrap compartilhadas durante o diagnóstico devem ser consideradas comprometidas. Elas precisam ser revogadas ou regeneradas imediatamente no Mailtrap, e os novos valores devem ser inseridos apenas no `.env` local, que não deve ser versionado nem anexado ao projeto. O log da aplicação não deve registrar `MAIL_PASSWORD`, tokens ou o conteúdo das credenciais.

O estado de release continua **No-Go** até a validação manual no XAMPP: configurar o novo remetente, reiniciar o Apache, cadastrar uma conta de teste e confirmar o recebimento no inbox do Mailtrap. Em caso de falha, coletar somente a mensagem sanitizada e o status, nunca a senha SMTP.


## 16. Release v2.0.0 — arquivamento dos artefatos legados

A v2.0.0 organiza os artefatos históricos sem misturá-los com o fluxo ativo. O arquivo PHP gerado anteriormente foi movido de `pages/` para `legacy/pages/`, e o JSON histórico foi movido de `boards/` para `legacy/boards/`. As pastas ativas permanecem apenas com arquivos de orientação (`.gitkeep`); novos quadros continuam usando exclusivamente a entrada dinâmica `board.php?id=<id>` e a persistência MySQL.

O bloco inalcançável de geração de páginas físicas foi removido de `new_board.php`. `delete_boards.php` deixou de tentar apagar arquivos físicos e agora remove somente registros pertencentes ao usuário autenticado. O diagnóstico administrativo passou a contar os artefatos em `legacy/`, e a documentação técnica foi atualizada para descrever a arquitetura vigente.

A migração é reversível pelo histórico Git, mas os arquivos em `legacy/` não devem ser reativados como fonte de dados. Antes de uma implantação, deve-se executar o fluxo de criação, abertura, edição, importação, exclusão e recarga de um quadro no XAMPP, confirmando que nenhum arquivo novo é criado em `pages/` ou `boards/`.
