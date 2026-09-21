<?php
declare(strict_types=1);

namespace App\Models;

final class PasswordReset extends Model
{
    protected string $table = 'password_resets';
    protected bool $softDeletes = false;

    public function findValidByHash(string $tokenHash): ?array
    {
        return $this->queryOne(
            'SELECT * FROM password_resets WHERE token_hash = :h AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()',
            ['h' => $tokenHash]
        );
    }

    public function invalidateForUser(int $userId): void
    {
        $this->execute('UPDATE password_resets SET used_at = UTC_TIMESTAMP() WHERE user_id = :u AND used_at IS NULL', ['u' => $userId]);
    }

    public function markUsed(int $id): void
    {
        $this->execute('UPDATE password_resets SET used_at = UTC_TIMESTAMP() WHERE id = :id', ['id' => $id]);
    }
}
