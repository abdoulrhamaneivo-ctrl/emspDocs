<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Fonctions utilitaires PDO partagées entre repositories.
 *
 * Remplace les anciens helpers mysqli globaux (emsp_schema_column_exists,
 * emsp_thumb_column_exists, etc.) par des méthodes PDO statiques avec cache.
 */
final class DatabaseHelper
{
    /** @var array<string, bool> */
    private static array $tableCache = [];

    /** @var array<string, bool> */
    private static array $columnCache = [];

    public static function tableExists(PDO $pdo, string $table): bool
    {
        $table = trim($table);
        if ($table === '') {
            return false;
        }
        if (array_key_exists($table, self::$tableCache)) {
            return self::$tableCache[$table];
        }

        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($safeTable === '') {
            return self::$tableCache[$table] = false;
        }

        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($safeTable));
        self::$tableCache[$table] = $stmt !== false && $stmt->rowCount() > 0;
        return self::$tableCache[$table];
    }

    public static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        if ($safeTable === '' || $safeColumn === '') {
            return self::$columnCache[$key] = false;
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM `{$safeTable}` LIKE " . $pdo->quote($safeColumn));
        self::$columnCache[$key] = $stmt !== false && $stmt->rowCount() > 0;
        return self::$columnCache[$key];
    }

    public static function columnNullable(PDO $pdo, string $table, string $column): bool
    {
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        if ($safeTable === '' || $safeColumn === '') {
            return false;
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM `{$safeTable}` LIKE " . $pdo->quote($safeColumn));
        if ($stmt === false) {
            return false;
        }
        $row = $stmt->fetch();
        return is_array($row) && strtoupper((string) ($row['Null'] ?? 'NO')) === 'YES';
    }

    public static function thumbColumnExists(PDO $pdo): bool
    {
        return self::columnExists($pdo, 'documents', 'thumb_path');
    }

    public static function ensureThumbColumn(PDO $pdo): void
    {
        if (!self::thumbColumnExists($pdo)) {
            try {
                $pdo->exec("ALTER TABLE documents ADD COLUMN thumb_path VARCHAR(500) DEFAULT '' AFTER file_path");
                // Invalidate cache
                unset(self::$columnCache['documents.thumb_path']);
            } catch (\Throwable) {
                // Ignore if column already exists or permission denied
            }
        }
    }

    /** Colonne display_order pour tri manuel des médias. */
    public static function ensureMediaColumns(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'media')) {
            return;
        }

        if (!self::columnExists($pdo, 'media', 'display_order')) {
            try {
                $pdo->exec('ALTER TABLE media ADD COLUMN display_order INT NOT NULL DEFAULT 0 AFTER status');
                unset(self::$columnCache['media.display_order']);
            } catch (\Throwable) {
                // Ignore if column already exists or permission denied
            }
        }
    }

    /** Colonnes éditoriales filières (titre court, description, photo). */
    public static function ensureFiliereEditorialColumns(PDO $pdo): void
    {
        $definitions = [
            'summary' => "ALTER TABLE filieres ADD COLUMN summary VARCHAR(200) NOT NULL DEFAULT '' AFTER status",
            'description_html' => "ALTER TABLE filieres ADD COLUMN description_html MEDIUMTEXT NULL AFTER summary",
            'cover_image_path' => "ALTER TABLE filieres ADD COLUMN cover_image_path VARCHAR(500) NOT NULL DEFAULT '' AFTER description_html",
        ];

        foreach ($definitions as $column => $sql) {
            if (self::columnExists($pdo, 'filieres', $column)) {
                continue;
            }
            try {
                $pdo->exec($sql);
                unset(self::$columnCache['filieres.' . $column]);
            } catch (\Throwable) {
                // Ignore if column already exists or permission denied
            }
        }

        self::ensureFiliereFormationProgramColumn($pdo);
    }

    /** Regroupement filières sous un programme (ex. FS-MENUM). */
    public static function ensureFiliereFormationProgramColumn(PDO $pdo): void
    {
        if (self::columnExists($pdo, 'filieres', 'formation_program')) {
            return;
        }

        try {
            $pdo->exec("ALTER TABLE filieres ADD COLUMN formation_program VARCHAR(32) NOT NULL DEFAULT '' AFTER cover_image_path");
            unset(self::$columnCache['filieres.formation_program']);
        } catch (\Throwable) {
            // Ignore if column already exists or permission denied
        }
    }

    public static function pendingMatiereEnabled(PDO $pdo): bool
    {
        return self::columnExists($pdo, 'documents', 'matiere_label_pending');
    }

    public static function documentFilieresEnabled(PDO $pdo): bool
    {
        return self::tableExists($pdo, 'document_filieres');
    }

    /**
     * Récupère les labels de filières pour un ensemble de documents.
     *
     * @param int[] $documentIds
     * @return array<int, string[]> Map docId => [nom1, nom2, ...]
     */
    public static function fetchDocumentFiliereLabels(PDO $pdo, array $documentIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $documentIds), fn(int $id) => $id > 0)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if (self::documentFilieresEnabled($pdo)) {
            $sql = "SELECT df.document_id, f.name
                    FROM document_filieres df
                    JOIN filieres f ON f.id = df.filiere_id
                    WHERE df.document_id IN ($placeholders)
                    ORDER BY f.name";
        } else {
            $sql = "SELECT d.id AS document_id, f.name
                    FROM documents d
                    LEFT JOIN filieres f ON f.id = d.filiere_id
                    WHERE d.id IN ($placeholders)
                    ORDER BY f.name";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();

        $labels = [];
        foreach ($rows as $row) {
            $docId = (int) ($row['document_id'] ?? 0);
            $name = trim((string) ($row['name'] ?? ''));
            if ($docId > 0 && $name !== '') {
                $name = function_exists('emsp_fix_mojibake') ? emsp_fix_mojibake($name) : $name;
                $labels[$docId][$name] = $name;
            }
        }

        // Deduplicate: convert sets to indexed arrays
        return array_map('array_values', $labels);
    }

    /**
     * Synchronise les filières d'un document (table pivot document_filieres
     * + colonne legacy filiere_id).
     *
     * @param int[] $filiereIds
     */
    public static function syncDocumentFilieres(PDO $pdo, int $documentId, array $filiereIds): void
    {
        if ($documentId <= 0) {
            return;
        }

        $filiereIds = array_values(array_unique(array_filter(array_map('intval', $filiereIds), fn(int $id) => $id > 0)));
        $legacyId = $filiereIds[0] ?? null;

        // Mettre à jour la colonne legacy filiere_id
        if ($legacyId !== null) {
            $stmt = $pdo->prepare("UPDATE documents SET filiere_id = :fid WHERE id = :did");
            $stmt->execute(['fid' => $legacyId, 'did' => $documentId]);
        } else {
            $stmt = $pdo->prepare("UPDATE documents SET filiere_id = NULL WHERE id = :did");
            $stmt->execute(['did' => $documentId]);
        }

        // Synchroniser la table pivot si elle existe
        if (!self::documentFilieresEnabled($pdo)) {
            return;
        }

        $del = $pdo->prepare("DELETE FROM document_filieres WHERE document_id = :did");
        $del->execute(['did' => $documentId]);

        if (empty($filiereIds)) {
            return;
        }

        $ins = $pdo->prepare("INSERT INTO document_filieres (document_id, filiere_id) VALUES (:did, :fid)");
        foreach ($filiereIds as $fid) {
            $ins->execute(['did' => $documentId, 'fid' => $fid]);
        }
    }

    /**
     * Assure la table audit_log (identique à l'ancien emsp_ensure_audit_log_table).
     */
    public static function ensureAuditLogTable(PDO $pdo): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                admin_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                target_type VARCHAR(50) NOT NULL,
                target_id INT NOT NULL DEFAULT 0,
                details TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_audit_admin (admin_id),
                KEY idx_audit_target (target_type, target_id),
                KEY idx_audit_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $ready = true;
        } catch (\Throwable) {
            $ready = false;
        }

        return $ready;
    }

    /**
     * Enregistre une entrée d'audit (remplace l'ancien log_audit global).
     */
    public static function logAudit(
        PDO $pdo,
        int $adminId,
        string $action,
        string $targetType,
        int $targetId = 0,
        ?string $details = null
    ): void {
        if ($adminId <= 0 || !self::ensureAuditLogTable($pdo)) {
            return;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO audit_log (admin_id, action, target_type, target_id, details) VALUES (:admin, :action, :type, :target, :details)"
        );
        $stmt->execute([
            'admin' => $adminId,
            'action' => $action,
            'type' => $targetType,
            'target' => $targetId,
            'details' => $details,
        ]);
    }
}
