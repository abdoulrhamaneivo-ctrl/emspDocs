<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use PDO;

final class UserAdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findPendingById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email, status, email_verified_at, verification_token FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** @return array{0: array<int,array<string,mixed>>, 1: array<int,array<string,mixed>>} [vérifiés, non-vérifiés] */
    public function pendingUsers(): array
    {
        $verifiedResult = $this->pdo->query("
            SELECT u.id, u.first_name, u.last_name, u.email,
                   u.created_at, u.email_verified_at, u.registration_method, u.verification_token,
                   u.student_card_path,
                   f.name AS filiere_name, l.name AS licence_name
            FROM users u
            LEFT JOIN filieres f ON f.id=u.filiere_id
            LEFT JOIN licences l ON l.id=u.licence_id
            WHERE u.status='pending' AND (u.email_verified_at IS NOT NULL OR u.verification_token IS NULL OR u.verification_token = '')
            ORDER BY u.created_at ASC");

        $verified = [];
        foreach ($verifiedResult->fetchAll() as $row) {
            $ts = !empty($row['created_at']) ? strtotime((string) $row['created_at']) : false;
            $row['created_label'] = $ts ? date('d/m/Y', $ts) : (string) ($row['created_at'] ?? '');
            $row['verified_label'] = !empty($row['email_verified_at']) ? (string) $row['email_verified_at'] : 'Confirmé';
            $verified[] = $row;
        }

        $unverifiedResult = $this->pdo->query("
            SELECT id, first_name, last_name, email, created_at, registration_method
            FROM users
            WHERE status='pending' AND (email_verified_at IS NULL AND (verification_token IS NOT NULL AND verification_token <> ''))
            ORDER BY created_at ASC");

        $unverified = $unverifiedResult->fetchAll();

        return [$verified, $unverified];
    }

    public function approve(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET status='active', status_updated_at=NOW(), rejection_reason=NULL WHERE id = :id AND status='pending'"
        );
        $stmt->execute(['id' => $userId]);
    }

    public function reject(int $userId, string $reason): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET status='rejected', rejection_reason = :reason, status_updated_at=NOW() WHERE id = :id AND status='pending'"
        );
        $stmt->execute(['reason' => $reason, 'id' => $userId]);
    }

    // -----------------------------------------------------------------
    // Liste / gestion complète des utilisateurs
    // -----------------------------------------------------------------

    /** @return array{0: array<int,array<string,mixed>>, 1: int} [utilisateurs, total] */
    public function filtered(string $role, string $status, string $search, int $perPage, int $offset): array
    {
        [$where, $params] = $this->buildFilterWhere($role, $status, $search);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN filieres f ON f.id=u.filiere_id $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status,
                       u.badge_level, u.upload_count, u.created_at, u.registration_method,
                       f.name AS filiere_name
                FROM users u
                LEFT JOIN filieres f ON f.id = u.filiere_id
                $where
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll();

        return [$users, $total];
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildFilterWhere(string $role, string $status, string $search): array
    {
        $where = 'WHERE 1=1';
        $params = [];

        if ($role !== '') {
            $where .= ' AND u.role = :role';
            $params['role'] = $role;
        }
        if ($status !== '') {
            $where .= ' AND u.status = :status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where .= ' AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR u.email LIKE :s3)';
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
        }

        return [$where, $params];
    }

    public function findRoleById(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        return $row ? strtolower((string) $row['role']) : null;
    }

    public function updateStatus(int $userId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $userId]);
    }

    public function updateRoleStatus(int $userId, string $role, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET role = :role, status = :status WHERE id = :id');
        $stmt->execute(['role' => $role, 'status' => $status, 'id' => $userId]);
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetch();
    }

    public function create(string $firstName, string $lastName, string $email, string $password, string $role, string $status, ?int $filiereId, ?int $licenceId): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (first_name, last_name, email, password_hash, role, status, registration_method, filiere_id, licence_id)
             VALUES (:first, :last, :email, :hash, :role, :status, 'school_email', :fid, :lid)"
        );
        $stmt->execute([
            'first' => $firstName, 'last' => $lastName, 'email' => $email,
            'hash' => $hash, 'role' => $role, 'status' => $status,
            'fid' => $filiereId, 'lid' => $licenceId,
        ]);
    }

    public function findForEdit(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, f.name AS filiere_name, l.name AS licence_name
             FROM users u
             LEFT JOIN filieres f ON f.id = u.filiere_id
             LEFT JOIN licences l ON l.id = u.licence_id
             WHERE u.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user) {
            foreach (['first_name', 'last_name', 'email', 'filiere_name', 'licence_name'] as $field) {
                if (isset($user[$field]) && is_string($user[$field])) {
                    $user[$field] = emsp_fix_mojibake($user[$field]);
                }
            }
        }

        return $user ?: null;
    }
}
