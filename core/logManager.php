<?php
/**
 * Log Manager - Gerencia arquivos de log com rotação automática
 * Evita que logs cresçam descontroladamente
 */

class LogManager {
    private static $logFile;
    private static $maxFileSize = 1048576; // 1MB em bytes
    private static $backupDir;

    private static function configurePaths(): void
    {
        $base = getenv('APP_LOG_PATH') ?: (dirname(__DIR__) . '/logs');
        self::$backupDir = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'archive';
        self::$logFile = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'debug.log';
    }
    
    /**
     * Inicializa o diretório de backup se necessário
     */
    public static function init() {
        self::configurePaths();
        if (!is_dir(self::$backupDir)) {
            mkdir(self::$backupDir, 0750, true);
        }
    }
    
    /**
     * Escreve uma mensagem de log com rotação automática
     */
    public static function log($level, $message, $context = []) {
        self::init();
        
        // Verifica se o arquivo precisa ser rotacionado
        if (file_exists(self::$logFile) && filesize(self::$logFile) > self::$maxFileSize) {
            self::rotate();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Rotaciona o arquivo de log (arquivo antigo é movido para backup)
     */
    private static function rotate() {
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = self::$backupDir . '/debug_log_' . $timestamp . '.txt';
        
        if (file_exists(self::$logFile)) {
            rename(self::$logFile, $backupFile);
        }
        
        // Limpa backups antigos (mais de 30 dias)
        self::cleanOldBackups();
    }
    
    /**
     * Remove backups de log com mais de 30 dias
     */
    private static function cleanOldBackups() {
        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);
        
        if (is_dir(self::$backupDir)) {
            $files = glob(self::$backupDir . '/debug_log_*.txt');
            foreach ($files as $file) {
                if (filemtime($file) < $thirtyDaysAgo) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Log de erro
     */
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Log de informação
     */
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log de sucesso
     */
    public static function success($message, $context = []) {
        self::log('SUCCESS', $message, $context);
    }
    
    /**
     * Log de aviso
     */
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }
}
?>
