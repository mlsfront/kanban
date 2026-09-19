<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

$conn = get_db_connection();
$stmt = $conn->prepare('SELECT is_admin FROM usuarios WHERE id = :uid LIMIT 1');
$stmt->execute([':uid' => $user_id]);
$user = $stmt->fetch();
if (!$user || (int)$user['is_admin'] !== 1) {
    http_response_code(403);
    exit('Acesso negado.');
}

$logFile = getenv('MAIL_LOG_PATH') ?: (__DIR__ . '/../logs/mail.log');
$raw = is_file($logFile) ? file_get_contents($logFile) : '';
$messages = [];
foreach (preg_split('/\R={10,}\R/', trim((string)$raw)) as $block) {
    if (trim($block) === '') continue;
    $bodyPos = strpos($block, 'Body:');
    $headers = $bodyPos === false ? $block : substr($block, 0, $bodyPos);
    $body = $bodyPos === false ? '' : ltrim(substr($block, $bodyPos + 5));
    $entry = ['date' => '', 'to' => '', 'subject' => '', 'body' => $body];
    foreach (preg_split('/\R/', $headers) as $line) {
        if (str_starts_with($line, 'Date:')) $entry['date'] = trim(substr($line, 5));
        if (str_starts_with($line, 'To:')) $entry['to'] = trim(substr($line, 3));
        if (str_starts_with($line, 'Subject:')) $entry['subject'] = trim(substr($line, 8));
    }
    $messages[] = $entry;
}
$messages = array_reverse($messages);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-mails em modo log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">E-mails registrados</h1>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>admin/logs.php">Voltar</a>
    </div>
    <?php if (!$messages): ?>
        <div class="alert alert-info">Nenhum e-mail registrado em modo log.</div>
    <?php endif; ?>
    <?php foreach ($messages as $message): ?>
        <article class="card mb-4 shadow-sm">
            <div class="card-header">
                <strong><?= htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8') ?></strong>
                <div class="small text-muted"><?= htmlspecialchars($message['date'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($message['to'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="card-body p-0">
                <iframe title="Corpo do e-mail" class="w-100 border-0" style="min-height: 360px" sandbox="allow-popups allow-popups-to-escape-sandbox" srcdoc="<?= htmlspecialchars($message['body'], ENT_QUOTES, 'UTF-8') ?>"></iframe>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<script src="<?= BASE_URL ?>assets/js/theme.js"></script>
</body>
</html>
