<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Controller;
use App\Core\LegacyDb;
use App\Repositories\AcademicRepository;
use App\Repositories\UserRepository;

final class AuthController extends Controller
{
    // ---------------------------------------------------------------
    // LOGIN
    // ---------------------------------------------------------------

    public function showLogin(): void
    {
        $this->redirectIfAuthenticated();
        $this->view('auth/login', ['old' => []]);
    }

    public function login(): void
    {
        $con = LegacyDb::mysqli();

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('login');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ip = emsp_client_ip();

        $rate = rate_limit_check($ip, $con);
        if (!empty($rate['blocked'])) {
            flash('warning', $this->rateLimitMessage((int) ($rate['retry_in'] ?? 0)));
            redirect('login');
        }

        if ($email === '' || $password === '') {
            rate_limit_record_failure($ip, $con);
            flash('danger', 'Email et mot de passe sont obligatoires pour se connecter.');
            redirect('login');
        }

        $users = new UserRepository(Database::pdo());
        $user = $users->findByEmailForLogin($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            rate_limit_record_failure($ip, $con);
            flash('danger', 'Adresse email ou mot de passe incorrect. Vous pouvez réinitialiser votre mot de passe.');
            redirect('login');
        }

        rate_limit_clear($ip, $con);

        // --- Statuts non actifs : mêmes règles que l'ancien logincode.php ---
        if ($user['status'] === 'pending') {
            session_regenerate_id(true);
            emsp_session_sync_auth_user($user);
            emsp_session_set_notif_count(0);
            emsp_session_set_notif_sections(['journal' => 0, 'media' => 0]);
            redirect('pending-status');
        }

        if ($user['status'] === 'rejected') {
            $reason = $user['rejection_reason'] ? ' Motif : ' . $user['rejection_reason'] : '';
            flash('danger', "Votre demande d'inscription a été refusée." . $reason . " Contactez l'administration si vous pensez qu'il s'agit d'une erreur.");
            redirect('login');
        }

        if ($user['status'] === 'suspended') {
            flash('danger', "Votre compte a été suspendu. Contactez l'administration pour plus d'informations.");
            redirect('login');
        }

        if ($user['status'] !== 'active') {
            flash('warning', "Votre compte n'est pas actif pour le moment. Contactez l'administration si besoin.");
            redirect('login');
        }

        // --- Connexion réussie ---
        session_regenerate_id(true);
        emsp_session_sync_auth_user($user);
        $users->touchLastLogin((int) $user['id']);
        try {
            (new \App\Services\NotificationService())->syncSessionCounters((int) $user['id']);
        } catch (\Throwable $e) {
            emsp_sync_notification_counters($con, (int) $user['id']);
        }

        flash('success', 'Bon retour, ' . $user['first_name'] . ' !');

        if (!empty($_SESSION['redirect_after_login'])) {
            $target = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $target);
            exit;
        }

        if (in_array($user['role'], ['admin', 'moderateur'], true)) {
            redirect('admin/validation-comptes');
        }
        redirect('dashboard');
    }

    // ---------------------------------------------------------------
    // REGISTER
    // ---------------------------------------------------------------

    public function showRegister(): void
    {
        $this->redirectIfAuthenticated();
        $con = LegacyDb::mysqli();
        $academic = new AcademicRepository(Database::pdo());
        $schoolEmailDomains = emsp_get_school_domains($con);

        $this->view('auth/register', [
            'filieres' => $academic->activeFilieres(),
            'licences' => $academic->activeLicences(),
            'errors' => $_SESSION['form_errors'] ?? [],
            'old' => $_SESSION['form_old'] ?? [],
            'schoolEmailDomains' => $schoolEmailDomains,
            'schoolEmailHint' => emsp_school_email_domains_hint($schoolEmailDomains),
        ]);
        unset($_SESSION['form_errors'], $_SESSION['form_old']);
    }

    public function register(): void
    {
        $con = LegacyDb::mysqli();

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('register');
        }

        $ip = emsp_client_ip();
        $rate = rate_limit_check($ip, $con);
        if (!empty($rate['blocked'])) {
            flash('warning', $this->rateLimitMessage((int) ($rate['retry_in'] ?? 0)));
            redirect('register');
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $registrationMethod = trim((string) ($_POST['registration_method'] ?? ''));
        $filiereId = (int) ($_POST['filiere_id'] ?? 0);
        $licenceId = (int) ($_POST['licence_id'] ?? 0);

        $schoolDomains = emsp_get_school_domains($con);
        $old = compact('firstName', 'lastName', 'email', 'registrationMethod', 'filiereId', 'licenceId');
        $errors = [];

        if ($firstName === '') {
            $errors['first_name'] = 'Le prénom est obligatoire.';
        }
        if ($lastName === '') {
            $errors['last_name'] = 'Le nom est obligatoire.';
        }
        $emailError = emsp_validate_registration_email($email, $registrationMethod, $schoolDomains);
        if ($emailError !== null) {
            $errors['email'] = $emailError;
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
        }
        if (!in_array($registrationMethod, ['school_email', 'manual_card'], true)) {
            $errors['registration_method'] = "Choisissez d'abord une méthode d'inscription.";
        }
        if ($filiereId <= 0) {
            $errors['filiere_id'] = 'Veuillez choisir une filière.';
        }
        if ($licenceId <= 0) {
            $errors['licence_id'] = 'Veuillez choisir un niveau.';
        }

        $academic = new AcademicRepository(Database::pdo());
        if ($filiereId > 0 && !$academic->filiereIsActive($filiereId)) {
            $errors['filiere_id'] = "Cette filière n'est pas disponible.";
        }
        if ($licenceId > 0 && !$academic->licenceIsActive($licenceId)) {
            $errors['licence_id'] = "Ce niveau n'est pas disponible.";
        }

        $users = new UserRepository(Database::pdo());
        if (empty($errors['email']) && $email !== '') {
            $existing = $users->findByEmailForRegistration($email);
            if ($existing && $this->redirectExistingRegistration($existing, $password, $ip, $con)) {
                return;
            }
            if ($existing) {
                $errors['email'] = 'Cette adresse email est déjà associée à un compte. '
                    . 'Essayez de vous <a href="' . h(url('login')) . '">connecter</a> ou de '
                    . '<a href="' . h(url('forgot-password')) . '">réinitialiser votre mot de passe</a>.';
            }
        }

        $studentCardPath = null;
        if (empty($errors) && $registrationMethod === 'manual_card') {
            $studentCardPath = $this->handleStudentCardUpload($errors);
            if ($studentCardPath === null && !isset($errors['student_card'])) {
                $errors['student_card'] = 'Carte étudiante obligatoire pour la validation manuelle.';
            }
        }

        if (!empty($errors)) {
            rate_limit_record_failure($ip, $con);
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old'] = $old;
            redirect('register');
        }

        // IMPORTANT : le même token doit être stocké en base ET envoyé par
        // email, sinon verify-email.php ne pourra jamais faire correspondre
        // le lien cliqué par l'utilisateur avec la ligne en base.
        $token = bin2hex(random_bytes(32));

        try {
            $newUserId = $users->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => $password,
                'registration_method' => $registrationMethod,
                'student_card_path' => $studentCardPath,
                'filiere_id' => $filiereId ?: null,
                'licence_id' => $licenceId ?: null,
                'token' => $token,
            ]);
        } catch (\PDOException $e) {
            if ((string) ($e->getCode()) === '23000' || str_contains($e->getMessage(), 'Duplicate')) {
                rate_limit_record_failure($ip, $con);
                $existing = $users->findByEmailForRegistration($email);
                if ($existing && $this->redirectExistingRegistration($existing, $password, $ip, $con)) {
                    return;
                }
                flash('warning', 'Cette adresse email est déjà inscrite. Connectez-vous ou vérifiez votre boîte mail.');
                redirect('login');
            }
            error_log('EMSP register PDO error: ' . $e->getMessage());
            flash('danger', 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer dans quelques instants.');
            redirect('register');
        } catch (\Throwable $e) {
            error_log('EMSP register error: ' . $e->getMessage());
            flash('danger', 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer dans quelques instants.');
            redirect('register');
        }

        rate_limit_clear($ip, $con);

        $sent = brevo_send_verification($newUserId, $email, $firstName, $lastName, $token);

        if (!$sent) {
            flash('warning', "Inscription enregistrée, mais l'email de confirmation n'a pas pu être envoyé. Vous pourrez le renvoyer depuis la page de suivi.");
        } else {
            flash('success', "Inscription réussie ! Un email de confirmation a été envoyé à $email. Vérifiez vos spams si vous ne le trouvez pas.");
        }

        redirect('login');
    }

    // ---------------------------------------------------------------
    // LOGOUT
    // ---------------------------------------------------------------

    public function logout(): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('dashboard');
        }

        // Rotation de session complète après déconnexion pour éviter de
        // conserver un identifiant de session réutilisable.
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();

        session_start();
        session_regenerate_id(true);
        flash('info', 'Vous avez été déconnecté avec succès.');
        redirect('login');
    }

    // ---------------------------------------------------------------
    // Helpers privés
    // ---------------------------------------------------------------

    // ---------------------------------------------------------------
    // VÉRIFICATION D'EMAIL
    // ---------------------------------------------------------------

    public function verifyEmail(): void
    {
        $con = LegacyDb::mysqli();
        $token = trim((string) ($_GET['token'] ?? ''));

        if ($token === '') {
            flash('danger', 'Ce lien de vérification est invalide ou expiré.');
            redirect('login');
        }

        $users = new UserRepository(Database::pdo());
        $user = $users->findByVerificationToken($token);

        if (!$user) {
            flash('danger', 'Ce lien de vérification est invalide ou expiré.');
            redirect('login');
        }

        if (!empty($user['email_verified_at'])) {
            flash('info', 'Ton adresse email a déjà été confirmée.');
            redirect('login');
        }

        $uid = (int) $user['id'];
        $loggedForUser = !empty(current_user()['id']) && (int) current_user()['id'] === $uid;

        $users->markEmailVerified($uid);

        $domains = emsp_get_school_domains($con);
        if (emsp_is_school_email($user['email'], $domains)) {
            $users->activateAccount($uid);
            brevo_send_account_approved($uid, $user['email'], $user['first_name'], $user['last_name']);
            flash('success', 'Ton compte a été activé. Tu peux maintenant te connecter.');
            redirect('login');
        }

        flash('success', 'Ton email est confirmé. Un administrateur doit maintenant valider ton compte.');
        header('Location: ' . url($loggedForUser ? 'pending-status?verified=1' : 'login'));
        exit;
    }

    // ---------------------------------------------------------------
    // RENVOI DE L'EMAIL DE VÉRIFICATION
    // ---------------------------------------------------------------

    /**
     * Appelée directement par le formulaire POST de /pending-status
     * (voir routes/web.php : '/resend-verification' → resendVerification()).
     */
    public function resendVerification(): void
    {
        $con = LegacyDb::mysqli();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . url('pending-status'));
            exit;
        }

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            header('Location: ' . url('pending-status'));
            exit;
        }

        $uid = (int) (current_user()['id'] ?? 0);
        if ($uid <= 0) {
            flash('warning', 'Veuillez vous connecter pour renvoyer l\'email.');
            redirect('login');
        }

        $users = new UserRepository(Database::pdo());
        $user = $users->findByIdForResend($uid);
        $emailConfirmed = $user ? (!empty($user['email_verified_at']) || empty($user['verification_token'])) : false;

        if (!$user || $user['status'] !== 'pending' || $emailConfirmed) {
            header('Location: ' . url('pending-status'));
            exit;
        }

        if (brevo_recent_email_exists($con, $uid, 'verification', 5)) {
            flash('warning', 'Veuillez patienter 5 minutes avant de renvoyer un email de confirmation.');
            header('Location: ' . url('pending-status'));
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $users->setVerificationToken($uid, $token);

        if (brevo_send_verification($uid, $user['email'], $user['first_name'], $user['last_name'], $token)) {
            flash('success', 'Un nouvel email de confirmation vient d\'être envoyé. Vérifiez votre boîte mail et vos spams.');
        } else {
            flash('danger', 'Le mail de confirmation n\'a pas pu être envoyé pour le moment. Réessayez dans quelques minutes.');
        }
        header('Location: ' . url('pending-status'));
        exit;
    }

    // ---------------------------------------------------------------
    // MOT DE PASSE OUBLIÉ
    // ---------------------------------------------------------------

    public function showForgotPassword(): void
    {
        $this->view('auth/forgot-password', [
            'sent' => (bool) ($_SESSION['forgot_password_sent'] ?? false),
            'emailMasked' => $_SESSION['forgot_password_email_masked'] ?? '',
        ]);
        unset($_SESSION['forgot_password_sent'], $_SESSION['forgot_password_email_masked']);
    }

    public function forgotPassword(): void
    {
        $con = LegacyDb::mysqli();

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('forgot-password');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $emailMasked = $this->maskEmail($email);

        if ($email !== '') {
            $users = new UserRepository(Database::pdo());
            $user = $users->findByEmailForReset($email);

            // Même délai que la cible existe ou non, pour ne pas révéler
            // par le temps de réponse si l'adresse est enregistrée.
            usleep(random_int(200000, 400000));

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $users->setResetToken((int) $user['id'], $token);
                $sent = brevo_send_password_reset(
                    (int) $user['id'],
                    $email,
                    (string) ($user['first_name'] ?? ''),
                    (string) ($user['last_name'] ?? ''),
                    $token
                );
                if (!$sent) {
                    error_log('EMSP forgot-password: Brevo send failed for user_id=' . (int) $user['id']);
                }
            }
        }

        // Message volontairement identique, que l'email existe ou non.
        $_SESSION['forgot_password_sent'] = true;
        $_SESSION['forgot_password_email_masked'] = $emailMasked;
        redirect('forgot-password');
    }

    // ---------------------------------------------------------------
    // RÉINITIALISATION DU MOT DE PASSE
    // ---------------------------------------------------------------

    public function showResetPassword(): void
    {
        $con = LegacyDb::mysqli();
        $token = trim((string) ($_GET['token'] ?? ''));

        if ($token === '' || !(new UserRepository(Database::pdo()))->findIdByValidResetToken($token)) {
            flash('danger', "Ce lien de réinitialisation n'est plus valide. Il expire après 1 heure.");
            redirect('forgot-password');
        }

        $this->view('auth/reset-password', ['token' => $token, 'error' => null]);
    }

    public function resetPassword(): void
    {
        $con = LegacyDb::mysqli();
        $token = trim((string) ($_POST['token'] ?? ''));
        $ip = emsp_client_ip();

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('forgot-password');
        }

        $users = new UserRepository(Database::pdo());
        if ($token === '' || !$users->findIdByValidResetToken($token)) {
            flash('danger', "Ce lien de réinitialisation n'est plus valide. Il expire après 1 heure.");
            redirect('forgot-password');
        }

        $rate = rate_limit_check($ip, $con);
        if (!empty($rate['blocked'])) {
            $this->view('auth/reset-password', ['token' => $token, 'error' => $this->rateLimitMessage((int) ($rate['retry_in'] ?? 0))]);
            return;
        }

        $pwd = (string) ($_POST['password'] ?? '');
        $pwd2 = (string) ($_POST['password_confirm'] ?? '');
        if (strlen($pwd) < 8 || $pwd !== $pwd2) {
            rate_limit_record_failure($ip, $con);
            $this->view('auth/reset-password', ['token' => $token, 'error' => 'Le mot de passe est invalide ou la confirmation ne correspond pas.']);
            return;
        }

        if (!$users->resetPasswordWithToken($token, $pwd)) {
            rate_limit_record_failure($ip, $con);
            flash('danger', "Ce lien de réinitialisation n'est plus valide. Il expire après 1 heure.");
            redirect('forgot-password');
        }

        rate_limit_clear($ip, $con);
        flash('success', 'Ton nouveau mot de passe est actif. Tu peux maintenant te connecter.');
        redirect('login');
    }

    // ---------------------------------------------------------------
    // Helpers privés
    // ---------------------------------------------------------------

    /** Utilisateur déjà connecté : ne pas renvoyer un compte pending vers dashboard. */
    private function redirectIfAuthenticated(): void
    {
        $user = current_user();
        if (!$user) {
            return;
        }

        $status = strtolower(trim((string) ($user['status'] ?? '')));
        if ($status === 'pending') {
            redirect('pending-status');
        }

        if ($status === 'active') {
            redirect('dashboard');
        }
    }

    /**
     * Compte déjà présent lors d'une (re)inscription : session + redirection claire.
     * @return bool true si une redirection a été effectuée
     */
    private function redirectExistingRegistration(array $existing, string $password, string $ip, \mysqli $con): bool
    {
        $status = strtolower(trim((string) ($existing['status'] ?? '')));
        $emailConfirmed = !empty($existing['email_verified_at']) || empty($existing['verification_token']);
        $hash = (string) ($existing['password_hash'] ?? '');
        $passwordMatches = $hash !== '' && password_verify($password, $hash);

        if ($status === 'pending' && !$emailConfirmed) {
            if ($passwordMatches) {
                rate_limit_clear($ip, $con);
                session_regenerate_id(true);
                emsp_session_sync_auth_user($existing);
                emsp_session_set_notif_count(0);
                emsp_session_set_notif_sections(['journal' => 0, 'media' => 0]);
                flash(
                    'warning',
                    'Compte en attente : vérifiez votre email pour continuer. Vous pouvez renvoyer l\'email de confirmation ci-dessous.'
                );
                redirect('pending-status');
            }

            rate_limit_record_failure($ip, $con);
            flash(
                'warning',
                'Cette adresse est déjà inscrite mais non confirmée. Connectez-vous avec votre mot de passe pour accéder au suivi, ou réinitialisez-le si besoin.'
            );
            redirect('login');
        }

        if ($status === 'pending' && $emailConfirmed) {
            if ($passwordMatches) {
                rate_limit_clear($ip, $con);
                session_regenerate_id(true);
                emsp_session_sync_auth_user($existing);
                emsp_session_set_notif_count(0);
                emsp_session_set_notif_sections(['journal' => 0, 'media' => 0]);
                flash(
                    'info',
                    'Votre email est confirmé. Votre compte est en attente de validation par l\'administration.'
                );
                redirect('pending-status');
            }

            rate_limit_record_failure($ip, $con);
            flash('warning', 'Cette adresse est déjà inscrite et en attente de validation. Connectez-vous pour suivre votre demande.');
            redirect('login');
        }

        if ($status === 'rejected') {
            rate_limit_record_failure($ip, $con);
            flash('danger', 'Une demande avec cette adresse a déjà été refusée. Contactez l\'administration si vous pensez qu\'il s\'agit d\'une erreur.');
            redirect('login');
        }

        if ($status === 'suspended') {
            rate_limit_record_failure($ip, $con);
            flash('danger', 'Ce compte est suspendu. Contactez l\'administration pour plus d\'informations.');
            redirect('login');
        }

        return false;
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return '';
        }
        [$local, $domain] = explode('@', $email, 2);
        if ($local === '' || $domain === '') {
            return '';
        }
        $maskedLocal = mb_strlen($local) <= 2
            ? mb_substr($local, 0, 1) . str_repeat('*', max(1, mb_strlen($local) - 1))
            : mb_substr($local, 0, 1) . str_repeat('*', max(2, mb_strlen($local) - 2)) . mb_substr($local, -1);
        return $maskedLocal . '@' . $domain;
    }

    private function rateLimitMessage(int $retryIn): string
    {
        if ($retryIn <= 0) {
            return 'Pour votre sécurité, réessayez plus tard.';
        }
        $mins = (int) ceil($retryIn / 60);
        $wait = $retryIn >= 60
            ? $mins . ' minute' . ($mins > 1 ? 's' : '')
            : $retryIn . ' seconde' . ($retryIn > 1 ? 's' : '');
        return 'Pour votre sécurité, réessayez dans ' . $wait . '.';
    }

    private function handleStudentCardUpload(array &$errors): ?string
    {
        $file = $_FILES['student_card'] ?? null;
        if (!is_array($file)) {
            $errors['student_card'] = 'Carte étudiante obligatoire.';
            return null;
        }

        $uploadErr = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $originalName = trim((string) ($file['name'] ?? ''));

        if ($uploadErr === UPLOAD_ERR_NO_FILE || $originalName === '') {
            $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
            $postMax = emsp_parse_size_to_bytes((string) ini_get('post_max_size'));
            if ($contentLength > 0 && $postMax > 0 && $contentLength > $postMax) {
                $errors['student_card'] = 'Le fichier dépasse la limite serveur (max '
                    . round($postMax / 1024 / 1024, 1) . ' Mo).';
            } else {
                $errors['student_card'] = 'Carte étudiante obligatoire.';
            }
            return null;
        }

        if ($uploadErr !== UPLOAD_ERR_OK) {
            $errors['student_card'] = $this->studentCardUploadErrorMessage($uploadErr);
            return null;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            $errors['student_card'] = "Erreur lors de l'envoi de la carte étudiante. Réessayez.";
            return null;
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = $this->normalizeStudentCardMime(
            emsp_detect_mime($tmpName),
            $ext,
            $tmpName
        );

        if (!in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
            $errors['student_card'] = 'Carte : JPG, PNG ou PDF uniquement.';
            return null;
        }

        $maxUpload = emsp_max_upload_size(10 * 1024 * 1024);
        if ((int) ($file['size'] ?? 0) > $maxUpload) {
            $errors['student_card'] = 'Carte trop lourde (max ' . round($maxUpload / 1024 / 1024, 1) . ' Mo).';
            return null;
        }

        if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
            $imageInfo = @getimagesize($tmpName);
            if ($imageInfo === false) {
                $errors['student_card'] = 'La carte image est invalide.';
                return null;
            }
        }

        $cardsDir = dirname(__DIR__, 2) . '/uploads/student-cards';
        if (!is_dir($cardsDir) && !@mkdir($cardsDir, 0755, true)) {
            $errors['student_card'] = "Impossible de préparer le dossier d'envoi. Contactez l'administration.";
            return null;
        }
        if (!is_writable($cardsDir) && !@chmod($cardsDir, 0755)) {
            $errors['student_card'] = "Le serveur ne peut pas enregistrer la carte. Contactez l'administration.";
            return null;
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
        ];
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];

        if (!move_uploaded_file($tmpName, $cardsDir . '/' . $filename)) {
            $errors['student_card'] = "Erreur lors de l'envoi de la carte étudiante. Réessayez.";
            return null;
        }

        @chmod($cardsDir . '/' . $filename, 0644);

        return $filename;
    }

    private function normalizeStudentCardMime(string $mime, string $ext, string $tmpPath): string
    {
        $aliases = [
            'image/pjpeg' => 'image/jpeg',
            'image/jpg' => 'image/jpeg',
            'image/x-png' => 'image/png',
            'application/x-pdf' => 'application/pdf',
        ];
        $mime = strtolower(trim($aliases[$mime] ?? $mime));

        if (in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
            return $mime;
        }

        $extMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        ];
        if (isset($extMap[$ext])) {
            return $extMap[$ext];
        }

        $header = @file_get_contents($tmpPath, false, null, 0, 4);
        if ($header === '%PDF') {
            return 'application/pdf';
        }

        return $mime;
    }

    private function studentCardUploadErrorMessage(int $uploadErr): string
    {
        $maxUpload = emsp_max_upload_size(10 * 1024 * 1024);
        $maxMb = round($maxUpload / 1024 / 1024, 1);

        return match ($uploadErr) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Carte trop lourde (max ' . $maxMb . ' Mo).',
            UPLOAD_ERR_PARTIAL => "Le fichier n'a pas été entièrement transféré. Réessayez.",
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION =>
                "Le serveur n'a pas pu enregistrer la carte. Contactez l'administration.",
            default => "Erreur lors de l'envoi de la carte étudiante. Réessayez.",
        };
    }
}
