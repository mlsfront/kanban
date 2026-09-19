<?php
namespace App\Config;

require_once __DIR__ . '/../../core/env.php';

class Mail
{
    public static function driver(): string
    {
        $driver = strtolower(trim((string)(getenv('MAIL_DRIVER') ?: 'log')));
        return in_array($driver, ['log', 'smtp', 'off'], true) ? $driver : 'log';
    }

    public static function enabled(): bool
    {
        return filter_var(getenv('MAIL_ENABLED') ?: '1', FILTER_VALIDATE_BOOLEAN);
    }

    public static function smtpHost(): string { return (string)(getenv('MAIL_HOST') ?: ''); }
    public static function smtpPort(): int { return (int)(getenv('MAIL_PORT') ?: 587); }
    public static function smtpUsername(): string { return (string)(getenv('MAIL_USERNAME') ?: ''); }
    public static function smtpPassword(): string { return (string)(getenv('MAIL_PASSWORD') ?: ''); }
    public static function smtpEncryption(): string { return (string)(getenv('MAIL_ENCRYPTION') ?: 'tls'); }
    public static function fromAddress(): string { return trim((string)(getenv('MAIL_FROM_ADDRESS') ?: '')); }
    public static function fromName(): string { return (string)(getenv('MAIL_FROM_NAME') ?: 'KanbanApp'); }
    public static function debug(): int { return (int)(getenv('MAIL_DEBUG') ?: 0); }
}
