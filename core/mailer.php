<?php
// core/mailer.php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Config\Mail;

class MailerService {

    /**
     * Envia um email de acordo com o DRIVER configurado (log ou smtp).
     *
     * @param string $to Destinatário
     * @param string $subject Assunto
     * @param string $body Corpo HTML
     * @return bool
     */
    public static function send($to, $subject, $body) {
        if (!Mail::enabled() || Mail::driver() === 'off') {
            return true;
        }
        if (Mail::driver() === 'log') {
            return self::logMail($to, $subject, $body);
        }
        return self::smtpMail($to, $subject, $body);
    }

    private static function logMail($to, $subject, $body) {
        $logFile = getenv('MAIL_LOG_PATH') ?: (dirname(__DIR__) . '/logs/mail.log');
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0750, true);
        }
        $date = date('Y-m-d H:i:s');
        
        $safeSubject = trim(preg_replace('/[\r\n]+/', ' ', (string)$subject));
        $safeTo = trim(preg_replace('/[\r\n]+/', ' ', (string)$to));
        $content = implode(PHP_EOL, [
            "Date: {$date}",
            "To: {$safeTo}",
            "Subject: {$safeSubject}",
            'Body:',
            (string)$body,
            str_repeat('=', 49),
            ''
        ]);

        file_put_contents($logFile, $content, FILE_APPEND | LOCK_EX);
        return true;
    }

    private static function smtpMail($to, $subject, $body) {
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = Mail::debug();
            $mail->isSMTP();
            $mail->Host       = Mail::smtpHost();
            $mail->SMTPAuth   = true;
            $mail->Username   = Mail::smtpUsername();
            $mail->Password   = Mail::smtpPassword();
            $mail->SMTPSecure = Mail::smtpEncryption();
            $mail->Port       = Mail::smtpPort();
            $mail->CharSet    = 'UTF-8';

            //Recipients
            $fromAddress = Mail::fromAddress();
            if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
                error_log('Erro ao enviar e-mail via SMTP: MAIL_FROM_ADDRESS inválido ou ausente. Use um endereço com domínio, por exemplo noreply@example.com.');
                return false;
            }
            $mail->setFrom($fromAddress, Mail::fromName());
            $mail->addAddress($to);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail via SMTP: {$mail->ErrorInfo}");
            return false;
        }
    }
}
