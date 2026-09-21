<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Default mailer: writes messages to storage/logs/mail.log so invitation and
 * password-reset links remain usable in local/demo environments without an
 * SMTP provider configured. Swap for an SMTP/PHPMailer adapter in production.
 */
final class LogMailer implements MailerInterface
{
    public function send(string $to, string $subject, string $body, ?string $htmlBody = null): bool
    {
        Logger::mail("To: $to | Subject: $subject | Body: $body");
        return true;
    }
}
