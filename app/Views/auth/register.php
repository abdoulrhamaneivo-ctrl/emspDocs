<section class="emsp-section emsp-register-page py-5">
    <div class="emsp-container emsp-container-narrow">
        <header class="emsp-register-intro">
            <span class="emsp-register-kicker">EMSP Docs · inscription</span>
            <h1>Créer votre compte étudiant</h1>
            <p>Trois étapes simples pour accéder aux ressources de l’École.</p>
        </header>

        <form action="<?= url('register') ?>" method="post" enctype="multipart/form-data" class="emsp-register-form" data-emsp-register-form data-school-domains="<?= h(json_encode($schoolEmailDomains ?? ['@emsp.int'], JSON_UNESCAPED_UNICODE)) ?>" data-school-hint="<?= h($schoolEmailHint ?? '@emsp.int') ?>">
            <?= csrf_field() ?>

            <section class="emsp-form-section">
                <div class="emsp-section-head"><span class="emsp-section-number">01</span><h2 class="emsp-section-title">Identité et connexion</h2></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="emsp-label" for="register-first-name">Prénom</label><input id="register-first-name" type="text" name="first_name" class="emsp-input" placeholder="Jean" value="<?= h($old['firstName'] ?? '') ?>" required><?php if (!empty($errors['first_name'])): ?><div class="text-danger text-xs mt-1"><?= h($errors['first_name']) ?></div><?php endif; ?></div>
                    <div class="col-md-6"><label class="emsp-label" for="register-last-name">Nom</label><input id="register-last-name" type="text" name="last_name" class="emsp-input" placeholder="Kouassi" value="<?= h($old['lastName'] ?? '') ?>" required><?php if (!empty($errors['last_name'])): ?><div class="text-danger text-xs mt-1"><?= h($errors['last_name']) ?></div><?php endif; ?></div>
                    <div class="col-12" data-emsp-register-email-wrap>
                        <label class="emsp-label" for="register-email">Adresse email</label>
                        <input
                            id="register-email"
                            type="email"
                            name="email"
                            class="emsp-input<?= !empty($errors['email']) ? ' is-invalid' : '' ?>"
                            placeholder="<?= h((($old['registrationMethod'] ?? 'school_email') === 'school_email') ? 'prenom.nom@emsp.int' : 'votre.email@exemple.com') ?>"
                            value="<?= h($old['email'] ?? '') ?>"
                            required
                            autocomplete="email"
                            data-emsp-register-email
                        >
                        <p class="emsp-field-help mb-0" data-emsp-email-help>
                            <?php if (($old['registrationMethod'] ?? 'school_email') === 'school_email'): ?>
                                Obligatoire : adresse institutionnelle EMSP (<?= h($schoolEmailHint ?? '@emsp.int') ?>).
                            <?php else: ?>
                                Toute adresse valide — votre carte étudiante sera vérifiée par l'administration.
                            <?php endif; ?>
                        </p>
                        <div class="text-danger text-xs mt-1 d-none" data-emsp-email-client-error role="alert" aria-live="polite"></div>
                        <?php if (!empty($errors['email'])): ?><div class="text-danger text-xs mt-1"><?= $errors['email'] ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6"><label class="emsp-label" for="register-password">Mot de passe <span>(8 caractères min.)</span></label><input id="register-password" type="password" name="password" class="emsp-input" minlength="8" autocomplete="new-password" required><?php if (!empty($errors['password'])): ?><div class="text-danger text-xs mt-1"><?= h($errors['password']) ?></div><?php endif; ?></div>
                    <div class="col-md-6"><label class="emsp-label" for="register-password-confirm">Confirmer le mot de passe</label><input id="register-password-confirm" type="password" name="password_confirm" class="emsp-input" minlength="8" autocomplete="new-password" required><?php if (!empty($errors['password_confirm'])): ?><div class="text-danger text-xs mt-1"><?= h($errors['password_confirm']) ?></div><?php endif; ?></div>
                </div>
            </section>

            <section class="emsp-form-section">
                <div class="emsp-section-head"><span class="emsp-section-number">02</span><h2 class="emsp-section-title">Parcours académique</h2></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="emsp-label" for="register-filiere">Filière d’étude</label><select id="register-filiere" name="filiere_id" class="emsp-select" required><option value="">— Sélectionner la filière —</option><?php foreach ($filieres as $f): ?><option value="<?= (int) $f['id'] ?>" <?= ((int) ($old['filiereId'] ?? 0) === (int) $f['id']) ? 'selected' : '' ?>><?= h($f['name']) ?></option><?php endforeach; ?></select><?php if (!empty($errors['filiere_id'])): ?><div class="text-danger text-xs mt-1"><?= h($errors['filiere_id']) ?></div><?php endif; ?></div>
                    <div class="col-md-6"><label class="emsp-label" for="register-licence">Niveau d’étude</label><select id="register-licence" name="licence_id" class="emsp-select" required><option value="">— Sélectionner le niveau —</option><?php foreach ($licences as $l): ?><option value="<?= (int) $l['id'] ?>" <?= ((int) ($old['licenceId'] ?? 0) === (int) $l['id']) ? 'selected' : '' ?>><?= h($l['name']) ?></option><?php endforeach; ?></select><?php if (!empty($errors['licence_id'])): ?><div class="text-danger text-xs mt-1"><?= h($errors['licence_id']) ?></div><?php endif; ?></div>
                </div>
            </section>

            <section class="emsp-form-section">
                <div class="emsp-section-head"><span class="emsp-section-number">03</span><h2 class="emsp-section-title">Vérification du statut</h2></div>
                <p class="emsp-field-help">Choisissez une seule méthode. La carte n’est demandée que si vous ne disposez pas d’une adresse email EMSP.</p>
                <div class="emsp-verification-options" role="radiogroup" aria-label="Méthode de vérification">
                    <label class="emsp-verification-option" for="method-school"><input type="radio" name="registration_method" id="method-school" value="school_email" <?= (($old['registrationMethod'] ?? 'school_email') === 'school_email') ? 'checked' : '' ?>><span class="emsp-verification-option__icon"><i class="bi bi-envelope-check"></i></span><span><strong>Email institutionnel</strong><small>Activation automatique après confirmation de votre adresse EMSP.</small></span></label>
                    <label class="emsp-verification-option" for="method-card"><input type="radio" name="registration_method" id="method-card" value="manual_card" <?= (($old['registrationMethod'] ?? '') === 'manual_card') ? 'checked' : '' ?>><span class="emsp-verification-option__icon"><i class="bi bi-person-vcard"></i></span><span><strong>Carte étudiante</strong><small>Validation manuelle par l’administration sous 24 h.</small></span></label>
                </div>
                <?php if (!empty($errors['registration_method'])): ?><div class="text-danger text-xs mt-2"><?= h($errors['registration_method']) ?></div><?php endif; ?>
                <div class="emsp-card-upload is-collapsed" data-emsp-card-upload>
                    <label class="emsp-label" for="register-student-card">Joindre votre carte étudiante</label>
                    <p class="emsp-field-help">PDF, PNG ou JPG · 10 Mo maximum.</p>
                    <input id="register-student-card" type="file" class="emsp-input" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                    <?php if (!empty($errors['student_card'])): ?><div class="text-danger text-xs mt-1"><?= h($errors['student_card']) ?></div><?php endif; ?>
                </div>
            </section>

            <button type="submit" class="emsp-btn emsp-btn-primary emsp-register-submit w-100 py-3 mb-3">Créer mon compte EMSP Docs <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            <p class="text-center text-sm text-secondary mb-0">Déjà inscrit ? <a href="<?= url('login') ?>" class="fw-semibold text-primary text-decoration-none">Se connecter</a></p>
        </form>
    </div>
</section>
<?php ob_start(); ?>
<script>
(function () {
  var form = document.querySelector('[data-emsp-register-form]');
  if (!form) return;
  var school = form.querySelector('#method-school');
  var card = form.querySelector('#method-card');
  var upload = form.querySelector('[data-emsp-card-upload]');
  var input = form.querySelector('#register-student-card');
  var emailInput = form.querySelector('[data-emsp-register-email]');
  var emailHelp = form.querySelector('[data-emsp-email-help]');
  var emailClientError = form.querySelector('[data-emsp-email-client-error]');
  var schoolHint = form.getAttribute('data-school-hint') || '@emsp.int';
  var schoolDomains = [];
  try {
    schoolDomains = JSON.parse(form.getAttribute('data-school-domains') || '[]');
  } catch (e) {
    schoolDomains = ['@emsp.int'];
  }

  function normalizeDomain(domain) {
    domain = String(domain || '').trim().toLowerCase();
    if (!domain) return '';
    if (domain.indexOf('@') === -1) {
      domain = '@' + domain.replace(/^@+/, '');
    }
    return domain;
  }

  function isSchoolEmail(value) {
    var email = String(value || '').trim().toLowerCase();
    if (!email) return false;
    var domains = schoolDomains.length ? schoolDomains : [schoolHint];
    return domains.some(function (domain) {
      return email.endsWith(normalizeDomain(domain));
    });
  }

  function clearClientEmailError() {
    if (!emailClientError || !emailInput) return;
    emailClientError.textContent = '';
    emailClientError.classList.add('d-none');
    emailInput.classList.remove('is-invalid');
  }

  function showClientEmailError(message) {
    if (!emailClientError || !emailInput) return;
    emailClientError.textContent = message;
    emailClientError.classList.remove('d-none');
    emailInput.classList.add('is-invalid');
  }

  function validateSchoolEmail(showMessage) {
    if (!school || !school.checked || !emailInput) {
      clearClientEmailError();
      return true;
    }
    var email = emailInput.value.trim();
    if (!email) {
      if (showMessage) {
        showClientEmailError('Adresse email institutionnelle obligatoire. Domaines acceptés : ' + schoolHint + '.');
      }
      return false;
    }
    if (!isSchoolEmail(email)) {
      if (showMessage) {
        showClientEmailError('Cette adresse n\'est pas une adresse institutionnelle EMSP. Utilisez un email se terminant par ' + schoolHint + ', ou choisissez « Carte étudiante ».');
      }
      return false;
    }
    clearClientEmailError();
    return true;
  }

  function syncMethod() {
    var requiresCard = !!(card && card.checked);
    upload.classList.toggle('is-collapsed', !requiresCard);
    if (requiresCard) {
      input.setAttribute('name', 'student_card');
      input.required = true;
    } else {
      input.removeAttribute('name');
      input.required = false;
      input.value = '';
    }
    school.closest('.emsp-verification-option').classList.toggle('is-selected', !requiresCard);
    card.closest('.emsp-verification-option').classList.toggle('is-selected', requiresCard);
    if (emailHelp && emailInput) {
      if (requiresCard) {
        emailHelp.textContent = 'Toute adresse valide — votre carte étudiante sera vérifiée par l\'administration.';
        emailInput.placeholder = 'votre.email@exemple.com';
      } else {
        emailHelp.textContent = 'Obligatoire : adresse institutionnelle EMSP (' + schoolHint + ').';
        emailInput.placeholder = 'prenom.nom' + (schoolHint.split(',')[0] || '@emsp.int');
      }
    }
    validateSchoolEmail(false);
  }

  form.addEventListener('submit', function (event) {
    if (card && card.checked) {
      upload.classList.remove('is-collapsed');
      input.setAttribute('name', 'student_card');
    }
    if (!validateSchoolEmail(true)) {
      event.preventDefault();
      if (emailInput) {
        emailInput.focus();
      }
    }
  });

  if (emailInput) {
    emailInput.addEventListener('blur', function () {
      validateSchoolEmail(true);
    });
    emailInput.addEventListener('input', function () {
      if (school && school.checked) {
        validateSchoolEmail(false);
      } else {
        clearClientEmailError();
      }
    });
  }

  school.addEventListener('change', syncMethod);
  card.addEventListener('change', syncMethod);
  syncMethod();
}());
</script>
<?php $page_scripts = ob_get_clean(); ?>
