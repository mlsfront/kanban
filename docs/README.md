# Documentação do KanbanApp

A documentação consolidada está em [`DOCUMENTACAO-TECNICA.md`](./DOCUMENTACAO-TECNICA.md). O documento contém o **PSD**, o **FSD**, a arquitetura/design, o guia operacional para XAMPP e hospedagem compartilhada, a análise de qualidade, os achados de segurança/performance/UI/UX, o resumo executivo e o backlog P0/P1/P2.

| Seção | Conteúdo |
|---|---|
| Resumo executivo | 10 principais achados, 10 quick wins e 5 riscos críticos |
| PSD | Objetivo, escopo, requisitos funcionais e não funcionais |
| FSD | Casos de uso, fluxos, regras e contratos HTTP |
| Arquitetura/design | Camadas, Mermaid, dados, autenticação, cache e erros |
| Guia operacional | XAMPP, dependências, deploy e manutenção |
| Auditoria | Bugs, gargalos, segurança e UI/UX com evidências |
| Backlog | Tarefas priorizadas como P0, P1 e P2 |
| Implementação | Branches, commits, decisões, testes e gates de produção |
| Melhorias recentes | Tarefas concluídas, IDs, JSON, tema, e-mail, logs e ownership |

A auditoria original foi feita estaticamente. O runtime PHP foi posteriormente disponibilizado para os testes locais; Composer, banco populado, SMTP, navegador e Apache de staging ainda precisam ser validados antes do merge final.

O plano de execução está em [`PLANO-DE-ACAO.md`](./PLANO-DE-ACAO.md), com roadmap de 30 dias úteis, responsáveis por papel, branches, critérios de aceite, matriz de testes, métricas, gates de release e checklist semanal. O acompanhamento da execução está em [`IMPLEMENTACAO-STATUS.md`](./IMPLEMENTACAO-STATUS.md), que registra decisões, commits, validações e bloqueios de produção. A branch `feature/requested-improvements`, commit `02f21c4`, concentra as melhorias funcionais e visuais mais recentes.

Para diagnóstico no XAMPP, administradores podem acessar `admin/diagnostics.php`. A auditoria estática reprodutível pode ser executada com `tools/audit_references.sh`; ela lista endpoints, imports, funções declaradas, possíveis helpers órfãos, duplicidades e artefatos legados.
