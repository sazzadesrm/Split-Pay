<?php
declare(strict_types=1);

namespace App\Models;

final class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->queryOne('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL', ['email' => $email]);
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }
}
