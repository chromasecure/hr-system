<?php
namespace App\Models;

use PDO;

class User
{
    public function __construct(private PDO $pdo) {}

    /**
     * Find user by primary key ID.
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT id,
                       username,
                       password_hash,
                       role,
                       branch_id,
                       IFNULL(is_active, 1) AS is_active
                  FROM users
                 WHERE id = :id
                 LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Find user by username (this is what the dashboard uses).
     */
    public function findByUsername(string $username): ?array
    {
        $sql = 'SELECT id,
                       username,
                       email,
                       password_hash,
                       role,
                       branch_id,
                       IFNULL(is_active, 1) AS is_active
                  FROM users
                 WHERE username = :username
                 LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute([':username' => $username]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Optional: if later you add an email column.
     * For now, if no email column exists, call will just return null.
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $sql = 'SELECT id,
                           username,
                           email,
                           password_hash,
                           role,
                           branch_id,
                           IFNULL(is_active, 1) AS is_active
                      FROM users
                     WHERE email = :email
                     LIMIT 1';
            $st = $this->pdo->prepare($sql);
            $st->execute([':email' => $email]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Throwable $e) {
            // email column may not exist; ignore and return null
            return null;
        }
    }

    /**
     * Find by username or email (whichever matches first).
     */
    public function findByIdentifier(string $identifier): ?array
    {
        $user = $this->findByUsername($identifier);
        if ($user) {
            return $user;
        }
        return $this->findByEmail($identifier);
    }
}
