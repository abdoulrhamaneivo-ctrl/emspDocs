<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmailForLogin(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, first_name, last_name, email, password_hash, role, status,
                    badge_level, email_verified_at, rejection_reason, photo_path
             FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Revalide l'état et le rôle d'un compte pour les requêtes authentifiées.
     * Cette lecture reste dans la couche PDO du MVC afin de ne pas dépendre
     * de la couche MySQLi legacy pour la sécurité de session.
     */
    public function findAuthState(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, role, status FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetch();
    }

    /** Compte existant lors d'une (re)inscription — statut et vérification email. */
    public function findByEmailForRegistration(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, first_name, last_name, email, password_hash, role, status,
                    email_verified_at, verification_token
             FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function touchLastLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $userId]);
    }

    /**
     * @param array{first_name:string,last_name:string,email:string,password:string,
     *              registration_method:string,student_card_path:?string,
     *              filiere_id:?int,licence_id:?int,token:string} $data
     * @return int L'id du nouvel utilisateur.
     */
    public function create(array $data): int
    {
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO users
                (first_name, last_name, email, password_hash, role, status,
                 registration_method, student_card_path, filiere_id, licence_id,
                 verification_token, email_verified_at, status_updated_at)
             VALUES (:first_name, :last_name, :email, :password_hash, 'etudiant', 'pending',
                     :registration_method, :student_card_path, :filiere_id, :licence_id,
                     :token, NULL, NULL)"
        );
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'registration_method' => $data['registration_method'],
            'student_card_path' => $data['student_card_path'],
            'filiere_id' => $data['filiere_id'],
            'licence_id' => $data['licence_id'],
            'token' => $data['token'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    // -----------------------------------------------------------------
    // Vérification d'email
    // -----------------------------------------------------------------

    public function findByVerificationToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, first_name, last_name, email, status, email_verified_at
             FROM users WHERE verification_token = :token LIMIT 1"
        );
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** @return bool true si la ligne a bien été marquée vérifiée */
    public function markEmailVerified(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = :id"
        );
        $stmt->execute(['id' => $userId]);
        $ok = $stmt->rowCount() > 0;

        if (!$ok) {
            // Le compte était peut-être déjà vérifié : on nettoie quand même le token.
            $fallback = $this->pdo->prepare("UPDATE users SET verification_token = NULL WHERE id = :id");
            $fallback->execute(['id' => $userId]);
        }

        return $ok;
    }

    public function activateAccount(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET status = 'active', status_updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $userId]);
    }

    public function findByIdForResend(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, first_name, last_name, email, status, email_verified_at, verification_token
             FROM users WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function setVerificationToken(int $userId, string $token): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET verification_token = :token WHERE id = :id");
        $stmt->execute(['token' => $token, 'id' => $userId]);
    }

    // -----------------------------------------------------------------
    // Mot de passe oublié / réinitialisation
    // -----------------------------------------------------------------

    public function findByEmailForReset(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function setResetToken(int $userId, string $token): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET reset_token = :token, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = :id"
        );
        $stmt->execute(['token' => $token, 'id' => $userId]);
    }

    public function findIdByValidResetToken(string $token): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM users WHERE reset_token = :token AND reset_token_expires_at > NOW() LIMIT 1"
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Transaction : revalide le token (FOR UPDATE), met à jour le mot de
     * passe, vérifie qu'il a bien été enregistré, purge le token.
     * @return bool succès
     */
    public function resetPasswordWithToken(string $token, string $newPassword): bool
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id FROM users WHERE reset_token = :token AND reset_token_expires_at > NOW() LIMIT 1 FOR UPDATE"
            );
            $stmt->execute(['token' => $token]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->pdo->rollBack();
                return false;
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd = $this->pdo->prepare(
                "UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id"
            );
            $upd->execute(['hash' => $hash, 'id' => $user['id']]);

            $verify = $this->pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
            $verify->execute(['id' => $user['id']]);
            $stored = $verify->fetch();

            if (!$stored || !password_verify($newPassword, (string) ($stored['password_hash'] ?? ''))) {
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('EMSP resetPasswordWithToken failed: ' . $e->getMessage());
            return false;
        }
    }

    // -----------------------------------------------------------------
    // Mon profil
    // -----------------------------------------------------------------

    public function findFullProfile(int $userId): array
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

        if (!$user) {
            $fallback = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $fallback->execute(['id' => $userId]);
            $user = $fallback->fetch();
        }

        return is_array($user) ? $user : [];
    }

    public function updateNames(int $userId, string $firstName, string $lastName): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET first_name = :first, last_name = :last WHERE id = :id');
        $stmt->execute(['first' => $firstName, 'last' => $lastName, 'id' => $userId]);
    }

    public function updatePhotoPath(int $userId, string $photoPath): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET photo_path = :path WHERE id = :id');
        $stmt->execute(['path' => $photoPath, 'id' => $userId]);
    }

    public function updateAcademic(int $userId, ?int $filiereId, ?int $licenceId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET filiere_id = :filiere_id, licence_id = :licence_id WHERE id = :id'
        );
        $stmt->execute([
            'filiere_id' => $filiereId,
            'licence_id' => $licenceId,
            'id' => $userId,
        ]);
    }

    /** @return true|string true si succès, sinon message d'erreur */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool|string
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, (string) ($row['password_hash'] ?? ''))) {
            return 'Mot de passe actuel incorrect. Vérifiez votre mot de passe actuel puis réessayez.';
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $upd->execute(['hash' => $hash, 'id' => $userId]);

        return true;
    }

    /** @return array{docs_approved:int, fav_count:int, dl_count:int} */
    public function profileStats(int $userId): array
    {
        return [
            'docs_approved' => $this->safeCount("SELECT COUNT(*) FROM documents WHERE uploader_id = :uid AND status='approved'", $userId),
            'fav_count' => $this->safeCount('SELECT COUNT(*) FROM favorites WHERE user_id = :uid', $userId),
            'dl_count' => $this->safeCount("SELECT COUNT(*) FROM history WHERE user_id = :uid AND action='download'", $userId),
        ];
    }

    private function safeCount(string $sql, int $userId): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['uid' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }
}
