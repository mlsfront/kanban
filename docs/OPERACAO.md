# Guia operacional

O guia completo de operação está em [`DOCUMENTACAO-TECNICA.md`](./DOCUMENTACAO-TECNICA.md), na seção **Guia operacional**. Ele cobre o ambiente XAMPP, banco, dependências Composer, Apache, deploy, limitações atuais e manutenção de logs.

Antes de produção, devem ser externalizados os segredos, definido o `APP_URL`, removidos os logs do webroot, validada a versão MySQL/MariaDB e criado um procedimento de backup/rollback.
