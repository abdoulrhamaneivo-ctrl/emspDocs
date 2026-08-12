<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class EmailService
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    public function recentEmailExists(int $userId, string $templateName, int $minutes = 5): bool
    {
        if ($userId <= 0 || $templateName === '') {
            return false;
        }
        $this->ensureEmailLogTable();

        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM email_log
             WHERE user_id = :uid
               AND template_name = :tpl
               AND status = 'sent'
               AND created_at >= DATE_SUB(NOW(), INTERVAL :mins MINUTE)
             LIMIT 1"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':tpl', $templateName, PDO::PARAM_STR);
        $stmt->bindValue(':mins', $minutes, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function sendVerification(int $userId, string $email, string $firstName, string $lastName, string $token): bool
    {
        $verifyUrl = $this->appUrl() . '/verify-email?token=' . urlencode($token);
        $html = $this->tplVerification($firstName, $verifyUrl);
        return $this->sendEmail($email, "$firstName $lastName", 'Vérifiez votre email', $html, $userId, 'verification');
    }

    public function sendPasswordReset(int $userId, string $email, string $firstName, string $lastName, string $token): bool
    {
        $resetUrl = $this->appUrl() . '/reset-password?token=' . urlencode($token);
        $html = $this->tplPasswordReset($firstName, $resetUrl);
        return $this->sendEmail($email, "$firstName $lastName", 'Réinitialiser votre mot de passe', $html, $userId, 'password_reset');
    }

    public function sendAccountApproved(int $userId, string $email, string $firstName, string $lastName): bool
    {
        $html = $this->tplAccountApproved($firstName);
        return $this->sendEmail($email, "$firstName $lastName", 'Votre compte EMSP Docs est activé', $html, $userId, 'account_approved');
    }

    public function sendAccountRejected(int $userId, string $email, string $firstName, string $lastName, string $motif): bool
    {
        $html = $this->tplAccountRejected($firstName, $motif);
        return $this->sendEmail($email, "$firstName $lastName", "Demande d'inscription refusée", $html, $userId, 'account_rejected');
    }

    public function sendDocApproved(int $uploaderId, string $email, string $firstName, string $lastName, string $docTitle): bool
    {
        $html = $this->tplDocApproved($firstName, $docTitle);
        return $this->sendEmail($email, "$firstName $lastName", 'Votre document a été approuvé', $html, $uploaderId, 'doc_approved');
    }

    public function sendDocRejected(int $uploaderId, string $email, string $firstName, string $lastName, string $docTitle, string $motif): bool
    {
        $html = $this->tplDocRejected($firstName, $docTitle, $motif);
        return $this->sendEmail($email, "$firstName $lastName", 'Votre document a été refusé', $html, $uploaderId, 'doc_rejected');
    }

    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        ?int $userId = null,
        string $tplName = 'custom'
    ): bool {
        $apiKey = trim($this->brevoApiKey());
        $senderEmail = trim($this->brevoSenderEmail());
        $senderName = $this->brevoSenderName();

        if (function_exists('emsp_fix_mojibake')) {
            $senderName = emsp_fix_mojibake((string) $senderName);
            $toName = emsp_fix_mojibake($toName);
            $subject = emsp_fix_mojibake($subject);
            $htmlBody = emsp_fix_mojibake($htmlBody);
        }

        if (
            $apiKey === ''
            || !function_exists('curl_init')
            || !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)
            || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)
        ) {
            error_log('EMSP BREVO: configuration invalide or cURL unavailable for template ' . $tplName);
            return false;
        }

        $payload = json_encode([
            'sender' => ['email' => $senderEmail, 'name' => $senderName],
            'to' => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'htmlContent' => $htmlBody,
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        if ($ch === false) {
            error_log('EMSP BREVO: curl_init failed for template ' . $tplName);
            return false;
        }
        $sslVerify = defined('APP_ENV') && APP_ENV === 'production';
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'api-key: ' . $apiKey,
            ],
        ]);

        $resp = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (defined('EMSP_DEBUG_EMAIL') && EMSP_DEBUG_EMAIL) {
            $logData = date('[Y-m-d H:i:s]') . " | To: $toEmail | HTTP: $httpCode | Err: $curlErr | Resp: $resp\n";
            $logFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'emsp_email.log';
            @file_put_contents($logFile, $logData, FILE_APPEND);
        }

        $ok = ($httpCode >= 200 && $httpCode < 300);

        if ($userId && $userId > 0) {
            $this->ensureEmailLogTable();
            $status = $ok ? 'sent' : 'failed';
            $stmt = $this->pdo->prepare('INSERT INTO email_log (user_id, email_to, subject, template_name, status) VALUES (:uid, :to, :subj, :tpl, :status)');
            $stmt->execute([
                'uid' => $userId,
                'to' => $toEmail,
                'subj' => $subject,
                'tpl' => $tplName,
                'status' => $status,
            ]);
        }

        return $ok;
    }

    private function ensureEmailLogTable(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS email_log (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            email_to VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NOT NULL,
            template_name VARCHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function appUrl(): string
    {
        $app = defined('APP_URL') ? rtrim((string) APP_URL, '/') : rtrim((string) config('base_url', ''), '/');
        if ($app !== '') {
            return $app;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        if ($base === '/' || $base === '\\') {
            $base = '';
        }
        if (str_ends_with($base, '/admin')) {
            $base = substr($base, 0, -6);
        }

        if (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) {
            return $scheme . '://' . $host . $base;
        }

        if ($host !== '') {
            error_log('EMSP WARNING: APP_URL non défini — fallback HTTP_HOST pour les liens email');
            return $scheme . '://' . $host . $base;
        }

        return '';
    }

    private function brevoApiKey(): string
    {
        if (defined('BREVO_API_KEY') && BREVO_API_KEY !== '') {
            return (string) BREVO_API_KEY;
        }
        return (string) config('brevo_api_key', '');
    }

    private function brevoSenderEmail(): string
    {
        if (defined('BREVO_SENDER_EMAIL') && BREVO_SENDER_EMAIL !== '') {
            return (string) BREVO_SENDER_EMAIL;
        }
        if (defined('BREVO_FROM_EMAIL') && BREVO_FROM_EMAIL !== '') {
            return (string) BREVO_FROM_EMAIL;
        }
        return (string) config('brevo_from_email', 'noreply@emsp.int');
    }

    private function brevoSenderName(): string
    {
        if (defined('BREVO_SENDER_NAME') && BREVO_SENDER_NAME !== '') {
            return (string) BREVO_SENDER_NAME;
        }
        if (defined('BREVO_FROM_NAME') && BREVO_FROM_NAME !== '') {
            return (string) BREVO_FROM_NAME;
        }
        return (string) config('brevo_from_name', 'EMSP Docs');
    }

    private function baseStyles(): string
    {
        return 'body{margin:0;padding:24px 12px;background:linear-gradient(180deg,#f4f8f5 0%,#eef4ef 100%);font-family:Inter,Arial,sans-serif;color:#132018;}'
            . '.wrap{max-width:580px;margin:0 auto;background:#fff;border-radius:0;overflow:hidden;border:1px solid rgba(0,107,60,.14);box-shadow:0 18px 42px rgba(0,43,24,.08);}'
            . '.header{padding:1.85rem 2rem;text-align:center;background:linear-gradient(135deg,#004d2b 0%,#006b3c 52%,#00804a 100%);}'
            . '.header-logo{color:#fff;font-size:1.32rem;font-weight:800;letter-spacing:-.02em;}'
            . '.eyebrow{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .8rem;border-radius:999px;background:rgba(200,148,22,.18);border:1px solid rgba(255,255,255,.18);color:#fff;font-size:.76rem;font-weight:700;margin-bottom:.8rem;}'
            . '.body{padding:2rem;}'
            . 'h2{color:#132018;font-size:1.28rem;font-weight:800;margin:0 0 .85rem;}'
            . 'p{color:#3f5248;font-size:.95rem;line-height:1.75;margin:0 0 1rem;}'
            . '.panel{padding:1rem 1.05rem;border-radius:0;background:#f7fbf8;border:1px solid rgba(0,107,60,.12);margin:1rem 0;}'
            . '.panel-copy{margin:0;}'
            . '.steps{margin:1.25rem 0 0;padding:0;list-style:none;}'
            . '.steps li{display:flex;gap:.75rem;align-items:flex-start;padding:.85rem 0;border-bottom:1px solid rgba(0,107,60,.08);}'
            . '.steps li:last-child{border-bottom:0;padding-bottom:0;}'
            . '.step-index{flex:0 0 2rem;width:2rem;height:2rem;display:inline-flex;align-items:center;justify-content:center;background:rgba(0,107,60,.1);color:#006b3c;font-weight:800;font-size:.82rem;}'
            . '.btn{display:inline-block;background:#006b3c;color:#fff;padding:.82rem 1.55rem;border-radius:0;font-weight:800;text-decoration:none;font-size:.92rem;margin:1rem 0 0;box-shadow:0 14px 28px rgba(0,107,60,.18);}'
            . '.btn-secondary{background:#c89416;box-shadow:0 14px 28px rgba(200,148,22,.16);}'
            . '.muted{font-size:.82rem;color:#667a6d;line-height:1.6;}'
            . '.footer{background:#f7fbf8;padding:1rem 2rem;text-align:center;font-size:.75rem;color:#667a6d;border-top:1px solid rgba(0,107,60,.08);}';
    }

    private function tplVerification(string $prenom, string $url): string
    {
        $css = $this->baseStyles();
        $prenom = h($prenom);
        $url = h($url);
        $pendingUrl = h($this->appUrl() . '/pending-status');
        return "<html><head><meta charset='UTF-8'><style>$css</style></head><body>"
            . "<div class='wrap'>"
            . "<div class='header'><div class='eyebrow'>Inscription EMSP Docs</div><div class='header-logo'>EMSP Docs</div></div>"
            . "<div class='body'>"
            . "<h2>Confirmez votre adresse email</h2>"
            . "<p>Bonjour $prenom,</p>"
            . "<p>Merci pour votre inscription sur la bibliothèque académique de l'EMSP. Une dernière étape sécurise votre compte&nbsp;: confirmer votre adresse email.</p>"
            . "<div class='panel'><p class='panel-copy'><strong>Action requise</strong> — cliquez sur le bouton ci-dessous depuis l'appareil de votre choix (téléphone ou ordinateur).</p></div>"
            . "<a href='$url' class='btn'>Confirmer mon email</a>"
            . "<ol class='steps'>"
            . "<li><span class='step-index'>1</span><span>Ouvrez cet email et appuyez sur <strong>Confirmer mon email</strong>.</span></li>"
            . "<li><span class='step-index'>2</span><span>Vous serez redirigé vers EMSP Docs — la page de suivi se mettra à jour.</span></li>"
            . "<li><span class='step-index'>3</span><span>Si besoin, consultez votre suivi&nbsp;: <a href='$pendingUrl'>$pendingUrl</a></span></li>"
            . "</ol>"
            . "<p class='muted'>Vous ne trouvez pas cet email&nbsp;? Vérifiez vos courriers indésirables (Spam, Promotions) ou renvoyez un nouveau lien depuis la page de suivi d'inscription.</p>"
            . "<p class='muted'>Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement ce message.</p>"
            . "</div>"
            . "<div class='footer'>EMSP Docs — École Multinationale Supérieure des Postes · Plateforme académique étudiante</div>"
            . "</div></body></html>";
    }

    private function tplPasswordReset(string $prenom, string $url): string
    {
        $css = $this->baseStyles();
        $prenom = h($prenom);
        $url = h($url);
        return "<html><head><meta charset='UTF-8'><style>$css</style></head><body><div class='wrap'><div class='header'><div class='eyebrow'>Sécurité EMSP Docs</div><div class='header-logo'>EMSP Docs</div></div><div class='body'><h2>Réinitialiser votre mot de passe</h2><p>Bonjour $prenom,</p><p>Vous avez demandé la réinitialisation de votre mot de passe. Le lien ci-dessous reste valable pendant 1 heure.</p><div class='panel'><p class='panel-copy'>Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.</p></div><a href='$url' class='btn'>Choisir un nouveau mot de passe</a></div><div class='footer'>EMSP Docs - Ce message a été envoyé automatiquement</div></div></body></html>";
    }

    private function tplAccountApproved(string $prenom): string
    {
        $css = $this->baseStyles();
        $prenom = h($prenom);
        $loginUrl = h($this->appUrl() . '/login');
        return "<html><head><meta charset='UTF-8'><style>$css</style></head><body><div class='wrap'><div class='header'><div class='eyebrow'>Validation de compte</div><div class='header-logo'>EMSP Docs</div></div><div class='body'><h2>Compte activé</h2><p>Bonjour $prenom,</p><p>Votre compte a été validé par l'administration. Vous pouvez maintenant vous connecter et accéder à la plateforme.</p><a href='$loginUrl' class='btn'>Se connecter</a></div><div class='footer'>EMSP Docs - Accès validé</div></div></body></html>";
    }

    private function tplAccountRejected(string $prenom, string $motif): string
    {
        $css = $this->baseStyles();
        $prenom = h($prenom);
        $motifHtml = ($motif !== '') ? "<p><strong>Motif :</strong> " . h($motif) . "</p>" : "";
        return "<html><head><meta charset='UTF-8'><style>$css</style></head><body><div class='wrap'><div class='header'><div class='eyebrow'>Suivi d'inscription</div><div class='header-logo'>EMSP Docs</div></div><div class='body'><h2>Demande refusée</h2><p>Bonjour $prenom,</p><p>Votre demande d'inscription n'a pas été validée.</p>$motifHtml<p>Si vous pensez qu'il s'agit d'une erreur, contactez l'administration.</p></div><div class='footer'>EMSP Docs - Information de compte</div></div></body></html>";
    }

    private function tplDocApproved(string $prenom, string $docTitle): string
    {
        $css = $this->baseStyles();
        $prenom = h($prenom);
        $docHtml = h($docTitle);
        $docsUrl = h($this->appUrl() . '/documents');
        return "<html><head><meta charset='UTF-8'><style>$css</style></head><body><div class='wrap'><div class='header'><div class='eyebrow'>Dépôt validé</div><div class='header-logo'>EMSP Docs</div></div><div class='body'><h2>Document approuvé</h2><p>Bonjour $prenom,</p><p>Votre document <strong>$docHtml</strong> a été approuvé et est maintenant visible dans la bibliothèque.</p><a href='$docsUrl' class='btn'>Voir la bibliothèque</a></div><div class='footer'>EMSP Docs - Bibliothèque étudiante</div></div></body></html>";
    }

    private function tplDocRejected(string $prenom, string $docTitle, string $motif): string
    {
        $css = $this->baseStyles();
        $prenom = h($prenom);
        $docHtml = h($docTitle);
        $motifHtml = ($motif !== '') ? "<p><strong>Motif :</strong> " . h($motif) . "</p>" : "";
        return "<html><head><meta charset='UTF-8'><style>$css</style></head><body><div class='wrap'><div class='header'><div class='eyebrow'>Dépôt à corriger</div><div class='header-logo'>EMSP Docs</div></div><div class='body'><h2>Document refusé</h2><p>Bonjour $prenom,</p><p>Votre document <strong>$docHtml</strong> a été refusé.</p>$motifHtml<p>Vous pouvez le corriger puis le soumettre à nouveau.</p></div><div class='footer'>EMSP Docs - Révision de dépôt</div></div></body></html>";
    }
}
