<?php

namespace App\Services;

use App\Core\App;

final class Mailer
{
    public static function send(string $toEmail, string $subject, string $body): bool
    {
        $mailConfig = App::config()['mail'] ?? [];
        $transport = strtolower(trim((string) ($mailConfig['transport'] ?? 'mail')));
        $fromAddress = trim((string) ($mailConfig['from_address'] ?? 'noreply@localhost'));
        $fromName = trim((string) ($mailConfig['from_name'] ?? 'BSC Elektronik'));

        if ($transport === 'log') {
            self::writeLog($toEmail, $subject, $body, $fromAddress, $fromName, 'log');
            return true;
        }

        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');
        $encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $encodedFromName . ' <' . $fromAddress . '>',
            'Reply-To: ' . $fromAddress,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        $sent = @mail($toEmail, $encodedSubject, $body, implode("\r\n", $headers));
        if (!$sent) {
            self::writeLog($toEmail, $subject, $body, $fromAddress, $fromName, 'mail-fallback');
        }

        return $sent;
    }

    private static function writeLog(
        string $toEmail,
        string $subject,
        string $body,
        string $fromAddress,
        string $fromName,
        string $mode
    ): void {
        $logDir = App::basePath('storage/logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logPath = $logDir . DIRECTORY_SEPARATOR . 'mail.log';
        $entry = '[' . date('Y-m-d H:i:s') . ']'
            . ' mode=' . $mode
            . ' to=' . $toEmail
            . ' from=' . $fromName . ' <' . $fromAddress . '>'
            . PHP_EOL
            . 'subject: ' . $subject . PHP_EOL
            . $body . PHP_EOL
            . str_repeat('-', 72) . PHP_EOL;
        file_put_contents($logPath, $entry, FILE_APPEND);
    }
}
