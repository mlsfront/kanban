# Contexto do Projeto — KanbanApp

Este documento apresenta uma visão consolidada do estado atual, das decisões arquiteturais e das melhorias de segurança implementadas no **KanbanApp** até a versão **2.0.0**. Ele serve como guia de referência para o entendimento da evolução do sistema e para a continuidade do desenvolvimento.

## Visão Geral e Estado Atual

O **KanbanApp** é uma aplicação web desenvolvida em PHP e MySQL destinada ao gerenciamento visual de tarefas. Originalmente dependente de arquivos físicos para a persistência de dados, o sistema foi transformado em uma plataforma centralizada no banco de dados, garantindo maior integridade e escalabilidade. A versão **2.0.0** marca a transição definitiva para uma arquitetura dinâmica, onde o ponto de entrada único `board.php` gerencia a renderização de todos os quadros com base em seus identificadores únicos.

A tabela a seguir resume os principais componentes e o estado da infraestrutura na versão atual:

| Componente | Descrição e Estado |
| --- | --- |
| **Arquitetura** | Dinâmica e centralizada; utiliza `board.php?id=<slug>` como entrada única. |
| **Persistência** | MySQL/MariaDB atua como fonte única de verdade; suporte a controle de versão otimista. |
| **Segurança** | Endurecimento P0 concluído, incluindo proteção CSRF, XSS e sessões seguras. |
| **Interface** | Suporte a tema escuro global com persistência de preferência e prevenção de flicker. |
| **Legado** | Artefatos históricos (páginas PHP e JSONs antigos) arquivados no diretório `legacy/`. |

## Melhorias de Segurança e Integridade

A segurança foi tratada como prioridade crítica, resultando na implementação de proteções contra vulnerabilidades comuns da web. A proteção contra **Cross-Site Request Forgery (CSRF)** foi aplicada a todas as operações que alteram o estado do sistema, enquanto a mitigação de **Cross-Site Scripting (XSS)** foi alcançada através da sanitização rigorosa de saídas e da substituição de métodos de renderização inseguros por alternativas que preservam a integridade do DOM.

No que tange à integridade dos dados, a migração para o MySQL permitiu a implementação de um sistema de **ownership**, vinculando cada quadro a um usuário específico através da coluna `usuario_id`. Além disso, o sistema agora utiliza um mecanismo de controle de concorrência que retorna o código **HTTP 409 (Conflict)** caso detecte tentativas de salvamento baseadas em versões desatualizadas do documento, prevenindo a perda acidental de informações em cenários de edição simultânea.

## Arquitetura de Comunicação e Diagnóstico

O sistema de e-mail foi modernizado para suportar múltiplos drivers, facilitando tanto o desenvolvimento quanto a produção. A configuração via **SMTP** foi validada com o serviço Mailtrap, exigindo agora um endereço de remetente com domínio válido para cumprir os requisitos de segurança dos provedores modernos.

| Driver de E-mail | Finalidade e Comportamento |
| --- | --- |
| **SMTP** | Envio real de mensagens via servidor configurado em `.env`. |
| **Log** | Registra metadados dos e-mails em `logs/mail.log` para fins de depuração. |
| **Off** | Desativa completamente as funcionalidades de envio de e-mail. |

Para garantir a observabilidade, foi criada uma página administrativa de **diagnóstico** que permite verificar a saúde do ambiente sem expor credenciais sensíveis. Os logs da aplicação foram centralizados em uma pasta protegida, garantindo que informações operacionais não sejam acessíveis publicamente via navegador.

## Estrutura de Diretórios e Procedimentos

A organização dos arquivos reflete a separação entre o código ativo e os artefatos históricos. A raiz do projeto foi limpa de arquivos de log e artefatos obsoletos, enquanto a lógica central permanece concentrada no diretório `core/`.

> **Nota Operacional:** O repositório de desenvolvimento preserva o histórico Git e os testes, mas a distribuição para produção deve ser realizada através do script `scripts/build.sh`. Este utilitário gera um pacote ZIP otimizado dentro da pasta `dist/`, excluindo automaticamente diretórios de desenvolvimento, segredos e histórico de versionamento.

As credenciais de banco de dados e SMTP são gerenciadas exclusivamente através do arquivo `.env`. É imperativo que este arquivo **nunca seja versionado** ou incluído em pacotes de distribuição. Em caso de exposição acidental de credenciais, como ocorreu durante a fase de diagnóstico, a rotação imediata das chaves é o procedimento padrão de segurança exigido.

---

**Data da última atualização:** 18 de agosto de 2026**Responsável Técnico:** MLSFront