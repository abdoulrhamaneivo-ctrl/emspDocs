<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class RateLimiterService
{
    public function __construct(private ?PDO $pdo = null)
    {
    }

    private function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = Database::pdo();
        }
        return $this->pdo;
    }

    /**
     * Obtenir l'IP client sécurisée en tenant compte des reverse proxies configurés.
     */
    public function clientIp(): string
    {
        $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote === '' || !filter_var($remote, FILTER_VALIDATE_IP)) {
            $remote = '0.0.0.0';
        }

        if (!in_array($remote, $this->trustedProxyIps(), true)) {
            return $remote;
        }

        $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwarded !== '') {
            foreach (explode(',', $forwarded) as $candidate) {
                $candidate = trim($candidate);
                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        return $remote;
    }

    /**
     * Vérifier si l'IP est bloquée suite à un trop grand nombre d'échecs.
     *
     * @return array{blocked: bool, retry_in: int}
     */
    public function check(string $ip): array
    {
        if ($ip === '') {
            return ['blocked' => false, 'retry_in' => 0];
        }

        $stmt = $this->pdo()->prepare('SELECT attempts, UNIX_TIMESTAMP(last_attempt) AS last_ts FROM login_attempts WHERE ip = :ip LIMIT 1');
        $stmt->execute(['ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['blocked' => false, 'retry_in' => 0];
        }

        $attempts = (int) ($row['attempts'] ?? 0);
        $lastTs = (int) ($row['last_ts'] ?? 0);
        $blocked = false;
        $retryIn = 0;

        if ($attempts >= 5) {
            $delta = time() - $lastTs;
            if ($delta < 900) {
                $blocked = true;
                $retryIn = 900 - $delta;
            }
        }

        return ['blocked' => $blocked, 'retry_in' => max(0, $retryIn)];
    }

    /**
     * Enregistrer un échec de connexion pour une IP.
     */
    public function recordFailure(string $ip): void
    {
        if ($ip === '') {
            return;
        }

        $stmt = $this->pdo()->prepare(
            'INSERT INTO login_attempts (ip, attempts) VALUES (:ip, 1)
             ON DUPLICATE KEY UPDATE attempts = attempts + 1'
        );
        $stmt->execute(['ip' => $ip]);
    }

    /**
     * Effacer le compteur d'échecs après une connexion réussie.
     */
    public function clear(string $ip): void
    {
        if ($ip === '') {
            return;
        }

        $stmt = $this->pdo()->prepare('DELETE FROM login_attempts WHERE ip = :ip');
        $stmt->execute(['ip' => $ip]);
    }

    /**
     * Générer le message de temps d'attente lisible.
     */
    public function rateLimitMessage(int $retryInSeconds): string
    {
        $minutes = (int) ceil($retryInSeconds / 60);
        return sprintf(
            'Trop de tentatives infructueuses. Par sécurité, l\'accès est suspendu pendant %d minute%s.',
            $minutes,
            $minutes > 1 ? 's' : ''
        );
    }

    /**
     * @return string[]
     */
    private function trustedProxyIps(): array
    {
        $raw = '';
        if (defined('TRUSTED_PROXY_IPS')) {
            $raw = (string) TRUSTED_PROXY_IPS;
        } elseif (isset($_ENV['TRUSTED_PROXY_IPS'])) {
            $raw = (string) $_ENV['TRUSTED_PROXY_IPS'];
        }

        $trusted = [];
        foreach (explode(',', $raw) as $ip) {
            $ip = trim($ip);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                $trusted[] = $ip;
            }
        }

        return array_values(array_unique($trusted));
    }
}
