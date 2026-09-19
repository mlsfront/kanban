# KanbanApp v2.0.0

**Data:** 18 de agosto de 2026  
**Branch:** `feature/requested-improvements`

## Resumo

A versão 2.0.0 consolida a arquitetura dinâmica e orientada ao banco de dados, separando os artefatos históricos do código ativo. O acesso aos quadros continua sendo feito por `board.php?id=<id>`, e a fonte oficial de persistência continua sendo o MySQL/MariaDB.

## Alterações

| Área | Mudança |
|---|---|
| Arquivos PHP antigos | `pages/*.php` foi movido para `legacy/pages/`. |
| JSONs antigos | `boards/*/*.json` foi movido para `legacy/boards/`. |
| Criação de quadros | Removido o bloco obsoleto que gerava páginas PHP físicas. |
| Exclusão de quadros | `delete_boards.php` remove registros autorizados no banco, sem apagar artefatos físicos. |
| Diagnóstico | `admin/diagnostics.php` consulta os arquivos históricos dentro de `legacy/`. |
| Documentação | `DOCUMENTACAO-TECNICA.md` e `IMPLEMENTACAO-STATUS.md` refletem a arquitetura vigente. |

## Compatibilidade e migração

Os arquivos em `legacy/` são preservados exclusivamente para consulta e histórico. Eles não devem ser acessados como páginas públicas nem usados como fonte de dados. O deploy deve manter as pastas `pages/` e `boards/` sem artefatos gerados; os dados novos devem ser criados e atualizados no banco.

## Validação obrigatória

No XAMPP, deve-se criar um quadro, abrir `board.php?id=<id>`, criar e editar tarefas, importar um JSON, recarregar a página e excluir o quadro. Depois, confirme que a alteração persiste no banco e que nenhum novo arquivo é criado em `pages/` ou `boards/`. Também devem ser executados o lint PHP, os testes automatizados existentes e a verificação estática de referências.
