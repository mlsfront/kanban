# Plano de ação — KanbanApp

**Data-base:** 15 de agosto de 2026  
**Horizonte sugerido:** 30 dias úteis  
**Objetivo:** levar o KanbanApp a uma condição segura e funcional para staging e, depois, produção, corrigindo primeiro os riscos P0 e somente liberando a publicação após a validação dos critérios de aceite.

> Os responsáveis abaixo são **papéis**, não nomes de pessoas. Eles devem ser substituídos pelos integrantes reais da equipe na reunião de kickoff.

## 1. Critério de liberação geral

A versão não deve ser promovida para produção enquanto existir qualquer item P0 aberto, qualquer falha de autorização por ownership de quadro, qualquer XSS reproduzível, qualquer segredo hardcoded no código implantável ou qualquer migração não testada contra uma cópia restaurada do banco. O gate de produção também exige backup verificável, rollback ensaiado, testes automatizados mínimos e aprovação do responsável técnico e do responsável de segurança.

## 2. Papéis e responsabilidades

| Papel | Responsabilidade principal |
|---|---|
| PO/Responsável pelo produto | Confirmar regras de negócio, escopo, prioridade e critérios de aceite. |
| Líder técnico | Decidir arquitetura, revisar PRs, controlar dívida técnica e aprovar desenho de dados. |
| Desenvolvedor backend | Corrigir PHP, autenticação, autorização, banco, migrações e endpoints. |
| Desenvolvedor frontend | Corrigir renderização, XSS, importação, UX, acessibilidade e autosave. |
| QA/Engenharia de qualidade | Criar casos de teste, executar regressão, evidenciar resultados e controlar defeitos. |
| Segurança | Revisar CSRF, XSS, secrets, headers, sessão, logs e teste de abuso. |
| DevOps/Infra | Configurar ambientes, variáveis, permissões, backup, observabilidade e deploy. |
| Documentação | Atualizar PSD/FSD/arquitetura, changelog, runbook e decisões técnicas. |

## 3. Roadmap de 30 dias úteis

As datas são relativas ao kickoff. Considerando início em **17/08/2026**, o calendário abaixo é uma referência; deve ser ajustado à disponibilidade da equipe.

| Fase | Janela | Resultado esperado | Gate |
|---|---:|---|---|
| F0 — Preparação | Dias 1–2 | Ambiente reproduzível, baseline e regras confirmadas | Nenhuma implementação sem backup e banco de teste |
| F1 — Segurança crítica | Dias 3–7 | P0 corrigido e testado | Nenhum P0 aberto |
| F2 — Integridade e persistência | Dias 8–13 | Migração, ownership, JSON e concorrência estabilizados | Dados preservados em testes de conflito |
| F3 — Operação e performance | Dias 14–18 | Segredos, logs, índices, retenção e deploy corrigidos | Staging operacional e observável |
| F4 — Qualidade e UX | Dias 19–24 | Testes, acessibilidade e bugs funcionais corrigidos | Regressão verde |
| F5 — Staging e release | Dias 25–30 | UAT, backup/rollback e decisão de produção | Aprovação formal de release |

## 4. F0 — Preparação e baseline

| ID | Ação | Responsável | Prazo | Dependências | Critério de aceite |
|---|---|---|---:|---|---|
| F0-01 | Confirmar se slug é único globalmente ou por usuário e se admin pode acessar qualquer quadro | PO + líder técnico | D1 | Nenhuma | Decisão registrada em ADR e PSD atualizado |
| F0-02 | Criar ambiente de teste com PHP 8.x, Apache, MySQL/MariaDB e Composer | DevOps | D1–D2 | Acesso ao XAMPP/servidor | Aplicação inicia e schema é aplicado a partir do zero |
| F0-03 | Criar fixture com usuários, admin, quadro válido, JSON inválido e tokens | QA + backend | D2 | F0-02 | Fixture reproduzível e sem dados reais |
| F0-04 | Registrar baseline de segurança e performance | Segurança + QA | D2 | F0-02 | Casos de teste iniciais e tempos de referência arquivados |
| F0-05 | Criar branch de integração `hardening/staging-baseline` a partir de `main` | Líder técnico | D1 | F0-01 | Branch publicada e protegida por PR |

## 5. F1 — Segurança crítica P0

### 5.1 CSRF e controles de sessão

| ID | Ação | Responsável | Prazo | Critério de aceite |
|---|---|---|---:|---|
| SEC-01 | Criar helper central `core/security.php` com geração/validação de token CSRF e comparação constante | Backend | D3 | Token é criado por sessão e rejeita ausência, alteração e reutilização indevida |
| SEC-02 | Proteger POST de criação, exclusão, promoção, limpeza de logs, cadastro, recuperação e reset | Backend | D3–D4 | Todos os endpoints mutáveis retornam 403 sem token válido |
| SEC-03 | Configurar cookies `HttpOnly`, `Secure` em HTTPS e `SameSite=Lax` ou `Strict` conforme o fluxo | Backend + DevOps | D4 | Inspeção de headers/cookies confirma atributos em staging |
| SEC-04 | Adicionar reautenticação ou confirmação de senha para promoção de admin e limpeza total de logs | Backend + PO | D5 | Ação sensível não executa apenas com sessão antiga |
| SEC-05 | Adicionar testes CSRF automatizados e teste manual com formulário externo | QA + Segurança | D6–D7 | Casos negativos falham com 403 e casos válidos continuam funcionando |

### 5.2 XSS, escaping e Content Security Policy

| ID | Ação | Responsável | Prazo | Critério de aceite |
|---|---|---|---:|---|
| SEC-06 | Substituir `innerHTML` de títulos, tarefas, colunas e atributos de dados por `textContent`/DOM seguro | Frontend | D3–D5 | Payloads HTML/JS são exibidos como texto, sem execução |
| SEC-07 | Validar cor, ID, data, prioridade e tamanho máximo do JSON no cliente e servidor | Frontend + Backend | D4–D6 | Entradas fora do contrato são rejeitadas sem alterar o estado anterior |
| SEC-08 | Corrigir escaping ausente nas views administrativas | Backend | D4 | Nome/e-mail/página/IP são escapados por contexto |
| SEC-09 | Implantar CSP inicialmente em `Report-Only` e depois em modo bloqueante | Segurança + DevOps | D6–D7 | Nenhuma violação inesperada após inventário de scripts/CDNs |
| SEC-10 | Executar teste de regressão XSS armazenado, refletido e em importação JSON | QA + Segurança | D7 | Zero execução em navegador limpo e sessão autenticada |

### 5.3 Segredos, URL e exposição de arquivos

| ID | Ação | Responsável | Prazo | Critério de aceite |
|---|---|---|---:|---|
| SEC-11 | Mover `DB_*`, `APP_URL` e Mail para variáveis de ambiente ou arquivo fora do webroot | DevOps + Backend | D3–D5 | Repositório não contém credenciais efetivas; aplicação falha de forma segura sem configuração |
| SEC-12 | Remover o uso direto de `HTTP_HOST` e validar host permitido | Backend | D4 | Links são sempre derivados de URL configurada |
| SEC-13 | Mover `mail.log`, `debug.log`, arquivo de rotação e backups para fora do document root | DevOps | D5 | Requisição HTTP não consegue baixar logs |
| SEC-14 | Redigir tokens, corpos de e-mail e dados pessoais dos logs | Backend + Segurança | D5–D6 | Logs de teste não contêm token utilizável nem HTML de mensagem |
| SEC-15 | Revisar `.htaccess` e adicionar headers de segurança sem bloquear assets necessários | DevOps | D6–D7 | Teste de smoke confirma acesso legítimo e bloqueio de arquivos sensíveis |

## 6. F2 — Integridade, dados e funcionalidade P1

| ID | Ação | Responsável | Prazo | Critério de aceite |
|---|---|---|---:|---|
| DATA-01 | Reescrever `core/migrate_to_mysql.php` usando PDO, `usuario_id`, transação e modo CLI protegido | Backend | D8–D9 | Migração executa uma vez, é idempotente e produz relatório de erros |
| DATA-02 | Definir MySQL como fonte única e descontinuar leitura operacional de JSON público | Líder técnico + Backend | D8–D10 | `Board.reload()` usa endpoint autorizado ou é removido; `.json` não é necessário em runtime |
| DATA-03 | Centralizar `requireAuth()` e `requireAdmin()` e revisar ownership em todos os endpoints | Backend | D9–D10 | Usuário A nunca lê, grava ou exclui quadro de B, inclusive com IDs manipulados |
| DATA-04 | Criar schema versionado e procedimento de backup/rollback de migration | Backend + DevOps | D9–D11 | Migration aplicada em clone e revertida em ambiente descartável |
| DATA-05 | Criar validador de contrato do documento Kanban | Backend + Frontend | D10–D11 | JSON inválido retorna 422 e preserva documento anterior |
| DATA-06 | Implementar limite de corpo, limite de tarefas/colunas e timeout de request | Backend + DevOps | D11 | Payload acima do limite é rejeitado com erro controlado |
| DATA-07 | Implementar controle otimista com `version` ou `last_updated` esperado | Backend + Frontend | D11–D13 | Conflito retorna 409 e oferece recarga/merge, sem sobrescrita silenciosa |
| DATA-08 | Corrigir geração de ID de tarefa com UUID ou combinação robusta | Frontend | D12 | Teste de múltiplas abas não produz colisões observadas |
| DATA-09 | Corrigir alteração indevida do título do quadro ao editar tarefa | Frontend | D12 | Editar título/descrição de tarefa não altera `Kanban.data.title` |
| DATA-10 | Definir comportamento de exclusão de coluna e implementar arquivamento ou confirmação reforçada | PO + Frontend | D13 | Regra aprovada e teste confirma preservação ou exclusão intencional |

## 7. F3 — Operação, performance e observabilidade

| ID | Ação | Responsável | Prazo | Critério de aceite |
|---|---|---|---:|---|
| OPS-01 | Criar índice para `logs_acesso(usuario_id, data_hora)` e `boards(usuario_id, last_updated)` após verificar planos | DBA/Backend | D14 | `EXPLAIN` confirma uso adequado nos filtros críticos |
| OPS-02 | Definir retenção de logs e automatizar purge controlado | DevOps + Segurança | D14–D15 | Job documentado, auditável e com teste de retenção |
| OPS-03 | Reduzir o custo de log síncrono ou criar mecanismo assíncrono conforme volume | Líder técnico | D15–D16 | Latência p95 de página não aumenta materialmente por logging |
| OPS-04 | Adicionar request ID, logs estruturados e mensagens genéricas ao usuário | Backend + DevOps | D15–D16 | Erros correlacionáveis sem stack trace ou segredo na resposta |
| OPS-05 | Definir health check de aplicação e banco | DevOps | D16 | Endpoint/rotina indica indisponibilidade sem expor credenciais |
| OPS-06 | Criar backup diário, retenção e restauração ensaiada | DevOps | D16–D18 | Restauração completa em ambiente isolado dentro do RTO definido |
| OPS-07 | Documentar deploy, rollback, variáveis e permissões de diretório | Documentação + DevOps | D17–D18 | Runbook executado por outra pessoa sem conhecimento tácito |

## 8. F4 — Qualidade, testes e UX

| ID | Ação | Responsável | Prazo | Critério de aceite |
|---|---|---|---:|---|
| QA-01 | Adicionar `composer.lock` e scripts de lint/teste | Backend + DevOps | D19 | Instalação reproduzível e pipeline executa comandos documentados |
| QA-02 | Criar testes de autenticação, confirmação, reset e sessão | QA + Backend | D19–D20 | Caminhos felizes e negativos cobertos |
| QA-03 | Criar testes de ownership, admin, save, import e concorrência | QA + Backend | D20–D21 | Matriz de autorização verde |
| QA-04 | Criar testes frontend para renderização segura, filtros, ordenação e drag-and-drop | QA + Frontend | D20–D22 | Componentes principais têm casos de regressão |
| QA-05 | Fazer teste de acessibilidade por teclado e leitor de tela | QA + Frontend | D22 | Sem bloqueio de navegação nos fluxos de login e quadro |
| QA-06 | Corrigir foco de modal, labels, ARIA, botões clicáveis e feedback de autosave | Frontend | D22–D23 | Fluxos são operáveis sem mouse e mensagens são anunciadas |
| QA-07 | Executar teste de carga com 20/100/500 quadros e volume de logs representativo | QA + DevOps | D23–D24 | p95 e consumo ficam dentro dos limites aprovados |

## 9. F5 — Staging, UAT e release

| ID | Ação | Responsável | Prazo | Critério de aceite |
|---|---|---|---:|---|
| REL-01 | Publicar em staging com configuração equivalente à produção | DevOps | D25 | Smoke test de login, quadro, save, admin e e-mail concluído |
| REL-02 | Executar UAT com roteiro aprovado pelo PO | PO + QA | D25–D27 | Todos os RF-01 a RF-10 passam ou têm exceção formal |
| REL-03 | Executar varredura de segurança e revisão final de diff | Segurança + líder técnico | D27–D28 | Nenhum P0/P1 bloqueador e nenhuma credencial detectada |
| REL-04 | Ensaiar backup, restore e rollback da aplicação | DevOps | D28 | RTO/RPO medidos e registrados |
| REL-05 | Fazer reunião Go/No-Go e registrar decisão | PO + líder técnico + Segurança | D29 | Ata com riscos aceitos, responsáveis e plano de reversão |
| REL-06 | Publicar, monitorar e revisar métricas após 24/72 horas | DevOps + QA | D30+ | Sem regressão crítica e métricas dentro do baseline |

## 10. Estratégia de branches e PRs

Cada conjunto de mudanças deve usar uma branch curta derivada de `main` ou da branch de integração vigente. Os nomes recomendados são `security/csrf-session`, `security/xss-csp`, `security/config-logs`, `fix/migration-pdo`, `fix/board-ownership`, `fix/optimistic-save`, `perf/log-indexes`, `test/regression-suite` e `ux/accessibility-kanban`.

Cada PR deve conter contexto, risco, arquivos alterados, migrações, testes executados, evidência de segurança quando aplicável, impacto documental e plano de rollback. O merge exige pelo menos uma revisão do líder técnico; mudanças de segurança exigem também revisão de Segurança; migrations exigem validação do responsável por dados.

Os commits devem ser pequenos e descritivos, por exemplo: `security: adicionar validacao CSRF aos POSTs`, `fix: migrar rotina legada para PDO`, `test: cobrir ownership de boards`, `docs: atualizar runbook de deploy`.

## 11. Matriz mínima de testes

| Domínio | Caso positivo | Caso negativo | Evidência |
|---|---|---|---|
| Login | Usuário confirmado entra e recebe nova sessão | Senha inválida, e-mail não confirmado e sessão expirada | Relatório de testes e logs sanitizados |
| CSRF | POST com token correto executa | Ausente, inválido e de outra sessão retornam 403 | Requests reproduzíveis |
| Ownership | Dono lê/salva/exclui seu quadro | Outro usuário tenta manipular por ID | Respostas HTTP e estado do banco |
| XSS | Texto legítimo aparece corretamente | Payload em tarefa, coluna, import e admin não executa | Vídeo/screenshot e console limpo |
| JSON | Documento válido salva | Tipo errado, coluna inexistente, payload grande retorna 422 | Fixture antes/depois |
| Concorrência | Duas abas salvam versões distintas | Segunda versão antiga recebe 409 | Evidência de conflito e ausência de overwrite |
| Migração | Clone migra com usuário definido | Reexecução não duplica e erro faz rollback | Dump antes/depois |
| Logs | Retenção remove somente período previsto | Usuário sem privilégio não limpa logs | Auditoria de ação |
| UX | Fluxo por teclado e foco correto | Sem mouse não há bloqueio | Checklist WCAG interno |

## 12. Métricas de sucesso

| Métrica | Meta de aceite | Como medir | Frequência |
|---|---:|---|---|
| Vulnerabilidades P0 abertas | 0 | Tracker de segurança/PRs | A cada PR e release |
| XSS reproduzível | 0 payloads executados | Suíte de segurança e teste manual | A cada release |
| POST sem CSRF | 0 endpoints | Inventário de rotas + testes | A cada PR backend |
| Segredos hardcoded | 0 | Secret scanner e revisão | CI e release |
| Testes automatizados passando | 100% do conjunto obrigatório | CI | A cada PR |
| Cobertura de caminhos críticos | Login, ownership, save, import e admin cobertos | Relatório de cobertura | A cada sprint |
| Latência p95 de leitura do quadro | Definir baseline; alvo inicial ≤ 500 ms em staging | APM/log request ID | Diário em staging |
| Latência p95 de salvamento | Definir baseline; alvo inicial ≤ 800 ms em staging | Métrica do endpoint | Diário em staging |
| Conflitos silenciosos de save | 0 | Contagem de 409 versus overwrite | Contínua |
| Restauração de backup | 100% dos ensaios aprovados | Checklist de restore | Mensal e antes de release |
| Falhas de deploy/rollback | 0 bloqueadoras | Runbook e incidentes | A cada release |
| Registros sensíveis em logs | 0 tokens/senhas/corpos integrais | Amostragem e scanner | A cada release |

Os limites de latência são metas iniciais e devem ser recalibrados após o baseline real. Não se deve declarar conformidade de performance sem carga representativa e ambiente equivalente.

## 13. Atualização da documentação técnica

Ao concluir cada item, atualizar `DOCUMENTACAO-TECNICA.md` e o arquivo específico da seção afetada. Alterações de comportamento devem atualizar o FSD; mudanças de tabelas, endpoints ou ownership devem atualizar arquitetura/design; mudanças de configuração, backup ou deploy devem atualizar `OPERACAO.md`; novos riscos ou mitigados devem atualizar o resumo executivo e o backlog.

Cada decisão que altere uma premissa deve ser registrada em uma ADR contendo contexto, decisão, alternativas rejeitadas, consequências e data. O release deve incluir changelog, versão do schema, migrations aplicadas, testes executados e riscos residuais.

## 14. Checklist de acompanhamento semanal

| Pergunta | Evidência esperada |
|---|---|
| Algum P0 permanece aberto? | Lista de issues atualizada |
| O ambiente de staging está reproduzível? | Script/runbook executado |
| Os testes críticos estão verdes? | Link ou artefato do CI |
| Houve mudança de regra de negócio? | Aprovação do PO e FSD atualizado |
| Houve alteração de schema? | Migration, backup e plano de rollback |
| Houve nova superfície de segurança? | Revisão de Segurança |
| As métricas melhoraram ou regrediram? | Comparação contra baseline |
| A documentação reflete o código atual? | Checklist de release |

## 15. Riscos residuais e decisões pendentes

O plano depende de decisões sobre unicidade de slug, privilégio administrativo, exclusão de colunas, versão mínima do banco, política de senha, retenção de logs, provedor de e-mail, RTO/RPO e metas de latência. Sem essas decisões, a equipe pode corrigir o código, mas não consegue validar completamente o comportamento esperado do produto. Essas questões devem ser resolvidas em F0 e anexadas à documentação antes do Go/No-Go.

## Referências internas

[1]: ./DOCUMENTACAO-TECNICA.md "Documentação técnica e auditoria consolidada"
[2]: ../core/ui.js "Renderização de tarefas e colunas"
[3]: ../core/database.php "Configuração de banco"
[4]: ../core/config.php "Construção de BASE_URL"
[5]: ../core/kanban.js "Autosave e persistência"
[6]: ../core/migrate_to_mysql.php "Migração legada"
[7]: ../admin/users.php "Administração de usuários"
[8]: ../admin/logs.php "Limpeza de logs"
