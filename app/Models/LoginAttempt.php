<?php
declare(strict_types=1);

namespace App\Models;

final class LoginAttempt extends Model
{
    protected string $table = 'login_attempts';
    protected bool $softDeletes = false;

    public function record(string $email, string $ip, bool $successful): void
    {
        $this->insert([
            'email' => $email,
            'ip_address' => $ip,
            'was_successful' => $successful ? 1 : 0,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function recentFailedCount(string $email, int $windowMinutes): int
    {
        $row = $this->queryOne(
            'SELECT COUNT(*) c FROM login_attempts
             WHERE email = :email AND was_successful = 0
             AND attempted_at > (UTC_TIMESTAMP() - INTERVAL :mins MINUTE)',
            ['email' => $email, 'mins' => $windowMinutes]
        );
        return (int) ($row['c'] ?? 0);
    }
}
