(function () {
  'use strict';

  var form = document.querySelector('[data-emsp-register-form]');
  if (!form) return;

  var TOTAL_STEPS = 3;
  var currentStep = 1;

  var school = form.querySelector('#method-school');
  var card = form.querySelector('#method-card');
  var upload = form.querySelector('[data-emsp-card-upload]');
  var fileInput = form.querySelector('#register-student-card');
  var emailInput = form.querySelector('[data-emsp-register-email]');
  var emailHelp = form.querySelector('[data-emsp-email-help]');
  var emailClientError = form.querySelector('[data-emsp-email-client-error]');
  var passwordInput = form.querySelector('[data-emsp-password-strength]');
  var passwordConfirm = form.querySelector('#register-password-confirm');
  var passwordHint = form.querySelector('[data-emsp-password-hint]');
  var passwordReqs = form.querySelector('[data-emsp-password-requirements]');
  var submitBtn = form.querySelector('[data-emsp-register-submit]');
  var prevBtn = form.querySelector('[data-emsp-register-prev]');
  var nextBtn = form.querySelector('[data-emsp-register-next]');
  var progressText = document.querySelector('[data-emsp-register-progress]');
  var wizardSteps = form.querySelectorAll('[data-register-step]');
  var stepperItems = document.querySelectorAll('[data-register-progress-step]');
  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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

  function methodSelected() {
    return (school && school.checked) || (card && card.checked);
  }

  function clearClientEmailError() {
    if (!emailClientError || !emailInput) return;
    emailClientError.textContent = '';
    emailClientError.classList.add('d-none');
    emailInput.classList.remove('is-invalid');
    emailInput.removeAttribute('aria-invalid');
  }

  function showClientEmailError(message) {
    if (!emailClientError || !emailInput) return;
    emailClientError.textContent = message;
    emailClientError.classList.remove('d-none');
    emailInput.classList.add('is-invalid');
    emailInput.setAttribute('aria-invalid', 'true');
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
    if (!upload || !fileInput || !school || !card) return;
    var requiresCard = card.checked;
    upload.classList.toggle('is-collapsed', !requiresCard);
    if (requiresCard) {
      fileInput.setAttribute('name', 'student_card');
      fileInput.required = true;
    } else {
      fileInput.removeAttribute('name');
      fileInput.required = false;
      fileInput.value = '';
    }
    school.closest('.emsp-verification-option').classList.toggle('is-selected', !requiresCard);
    card.closest('.emsp-verification-option').classList.toggle('is-selected', requiresCard);
    if (emailHelp && emailInput) {
      if (requiresCard) {
        emailHelp.textContent = 'Adresse personnelle valide — votre carte étudiante sera vérifiée par l\'administration.';
        emailInput.placeholder = 'votre.email@exemple.com';
      } else {
        emailHelp.textContent = 'Obligatoire : adresse institutionnelle EMSP (' + schoolHint + ').';
        emailInput.placeholder = 'prenom.nom' + (schoolHint.split(',')[0] || '@emsp.int');
      }
    }
    if (currentStep >= 2) {
      validateSchoolEmail(false);
    } else {
      clearClientEmailError();
    }
    syncFormState();
  }

  function scorePassword(value) {
    var score = 0;
    if (!value) return 0;
    if (value.length >= 8) score += 1;
    if (value.length >= 12) score += 1;
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score += 1;
    if (/\d/.test(value)) score += 1;
    if (/[^A-Za-z0-9]/.test(value)) score += 1;
    return Math.min(score, 4);
  }

  function syncPasswordHint() {
    if (!passwordInput || !passwordHint) return;
    var value = passwordInput.value || '';
    var score = scorePassword(value);
    passwordHint.dataset.strength = String(score);
    var labels = [
      'Au moins 8 caractères — lettres et chiffres recommandés.',
      'Faible — ajoutez des chiffres ou majuscules.',
      'Correct — vous pouvez renforcer avec un symbole.',
      'Bon mot de passe.',
      'Excellent mot de passe.'
    ];
    var text = passwordHint.querySelector('.emsp-password-hint__text');
    if (text) text.textContent = labels[score] || labels[0];

    if (passwordReqs) {
      var items = passwordReqs.querySelectorAll('[data-req]');
      items.forEach(function (item) {
        var req = item.getAttribute('data-req');
        var met = false;
        if (req === 'length') met = value.length >= 8;
        if (req === 'mixed') met = /[a-zA-Z]/.test(value) && /\d/.test(value);
        if (req === 'case') met = /[a-z]/.test(value) && /[A-Z]/.test(value);
        if (req === 'symbol') met = /[^A-Za-z0-9]/.test(value);
        item.classList.toggle('is-met', met);
      });
    }
  }

  function isStep1Complete() {
    return methodSelected();
  }

  function isStep2Complete() {
    var first = form.querySelector('#register-first-name');
    var last = form.querySelector('#register-last-name');
    if (!first || !last || !emailInput) return false;
    if (!first.value.trim() || !last.value.trim() || !emailInput.value.trim()) return false;
    if (!emailInput.checkValidity()) return false;
    if (card && card.checked && fileInput && !fileInput.files.length) return false;
    if (!validateSchoolEmail(false)) return false;
    return true;
  }

  function isStep3Complete() {
    var filiere = form.querySelector('#register-filiere');
    var licence = form.querySelector('#register-licence');
    if (!filiere || !licence || !passwordInput || !passwordConfirm) return false;
    if (passwordInput.value.length < 8 || passwordInput.value !== passwordConfirm.value) return false;
    if (filiere.value === '' || licence.value === '') return false;
    return true;
  }

  function getStepElement(step) {
    return form.querySelector('[data-register-step="' + step + '"]');
  }

  function getStepFields(step) {
    var el = getStepElement(step);
    if (!el) return [];
    return Array.prototype.slice.call(el.querySelectorAll('input, select, textarea'));
  }

  function markFieldInvalid(field, message) {
    field.classList.add('is-invalid');
    field.setAttribute('aria-invalid', 'true');
    if (message && field.type !== 'radio') {
      var wrap = field.closest('.emsp-field') || field.parentElement;
      if (wrap) {
        var existing = wrap.querySelector('[data-emsp-wizard-error]');
        if (!existing) {
          existing = document.createElement('div');
          existing.className = 'emsp-field-error';
          existing.setAttribute('data-emsp-wizard-error', '');
          existing.setAttribute('role', 'alert');
          wrap.appendChild(existing);
        }
        existing.textContent = message;
      }
    }
  }

  function clearWizardErrors(step) {
    var el = getStepElement(step);
    if (!el) return;
    el.querySelectorAll('.is-invalid').forEach(function (field) {
      if (field === emailInput && emailClientError && !emailClientError.classList.contains('d-none')) return;
      field.classList.remove('is-invalid');
      field.removeAttribute('aria-invalid');
    });
    el.querySelectorAll('[data-emsp-wizard-error]').forEach(function (node) {
      node.remove();
    });
  }

  function validateStep(step, showMessages) {
    clearWizardErrors(step);
    var fields = getStepFields(step);
    var firstInvalid = null;
    var valid = true;

    fields.forEach(function (field) {
      if (field.type === 'file' && field.closest('.is-collapsed')) return;
      if (field.type === 'radio') return;
      if (!field.checkValidity()) {
        valid = false;
        markFieldInvalid(field);
        if (!firstInvalid) firstInvalid = field;
      }
    });

    if (step === 1) {
      if (!methodSelected()) {
        valid = false;
        var methodWrap = form.querySelector('.emsp-register-verify-tiles');
        if (methodWrap && showMessages) {
          var existing = methodWrap.parentElement.querySelector('[data-emsp-wizard-error]');
          if (!existing) {
            existing = document.createElement('div');
            existing.className = 'emsp-field-error mt-2';
            existing.setAttribute('data-emsp-wizard-error', '');
            existing.setAttribute('role', 'alert');
            methodWrap.parentElement.appendChild(existing);
          }
          existing.textContent = 'Choisissez une méthode de vérification pour continuer.';
        }
      }
    }

    if (step === 2 && emailInput) {
      if (!emailInput.value.trim()) {
        valid = false;
        markFieldInvalid(emailInput, 'Adresse email obligatoire.');
        if (!firstInvalid) firstInvalid = emailInput;
      } else if (!emailInput.checkValidity()) {
        valid = false;
        markFieldInvalid(emailInput, 'Adresse email invalide.');
        if (!firstInvalid) firstInvalid = emailInput;
      } else if (!validateSchoolEmail(showMessages)) {
        valid = false;
        if (!firstInvalid) firstInvalid = emailInput;
      }
      if (card && card.checked && fileInput && !fileInput.files.length) {
        valid = false;
        markFieldInvalid(fileInput, 'Veuillez joindre votre carte étudiante.');
        if (!firstInvalid) firstInvalid = fileInput;
      }
    }

    if (step === 3 && passwordInput && passwordConfirm) {
      if (passwordInput.value.length < 8) {
        valid = false;
        markFieldInvalid(passwordInput, 'Le mot de passe doit contenir au moins 8 caractères.');
        if (!firstInvalid) firstInvalid = passwordInput;
      }
      if (passwordConfirm.value !== passwordInput.value) {
        valid = false;
        markFieldInvalid(passwordConfirm, 'Les mots de passe ne correspondent pas.');
        if (!firstInvalid) firstInvalid = passwordConfirm;
      }
    }

    if (!valid && firstInvalid && showMessages) {
      firstInvalid.focus({ preventScroll: true });
      var scrollTarget = firstInvalid.closest('.emsp-field') || firstInvalid;
      scrollTarget.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'center' });
    }

    return valid;
  }

  function updateStepperUI(step) {
    stepperItems.forEach(function (el) {
      var n = parseInt(el.getAttribute('data-register-progress-step'), 10);
      el.classList.remove('is-active', 'is-complete');
      if (n < step) el.classList.add('is-complete');
      else if (n === step) el.classList.add('is-active');
    });

    if (progressText) {
      progressText.textContent = 'Étape ' + step + ' sur ' + TOTAL_STEPS;
    }
  }

  function updateNavButtons(step) {
    if (prevBtn) {
      if (step > 1) prevBtn.removeAttribute('hidden');
      else prevBtn.setAttribute('hidden', '');
    }
    if (nextBtn && submitBtn) {
      if (step < TOTAL_STEPS) {
        nextBtn.removeAttribute('hidden');
        submitBtn.setAttribute('hidden', '');
      } else {
        nextBtn.setAttribute('hidden', '');
        submitBtn.removeAttribute('hidden');
      }
    }
  }

  function showStep(step, direction) {
    if (step < 1 || step > TOTAL_STEPS) return;
    currentStep = step;

    wizardSteps.forEach(function (el) {
      var n = parseInt(el.getAttribute('data-register-step'), 10);
      var isTarget = n === step;
      el.classList.remove('is-active', 'is-hidden', 'is-entering', 'is-leaving', 'is-forward', 'is-backward');
      if (isTarget) {
        el.classList.add('is-active');
        if (direction && !prefersReducedMotion) {
          el.classList.add('is-entering', direction === 'forward' ? 'is-forward' : 'is-backward');
        }
        el.removeAttribute('hidden');
      } else {
        el.classList.add('is-hidden');
        el.setAttribute('hidden', '');
      }
    });

    updateStepperUI(step);
    updateNavButtons(step);
    syncMethod();
    syncFormState();

    var activeStep = getStepElement(step);
    if (activeStep && direction) {
      var firstInput = activeStep.querySelector('input:not([type="hidden"]):not([type="radio"]), select, textarea');
      if (firstInput) firstInput.focus({ preventScroll: true });
      else {
        var firstRadio = activeStep.querySelector('input[type="radio"]');
        if (firstRadio) firstRadio.focus({ preventScroll: true });
      }
    }
  }

  function syncFormState() {
    if (!submitBtn) return;
    var ready = isStep1Complete() && isStep2Complete() && isStep3Complete();
    submitBtn.disabled = !ready;
    submitBtn.setAttribute('aria-disabled', ready ? 'false' : 'true');
  }

  function scrollToFirstError() {
    var target = form.querySelector('.is-invalid, .emsp-field-error:not(.d-none), [aria-invalid="true"]');
    if (!target) return;
    var field = target.closest('[class*="col-"]') || target;
    field.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'center' });
    var input = field.querySelector('input, select, textarea');
    if (input) input.focus({ preventScroll: true });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      if (!validateStep(currentStep, true)) return;
      showStep(currentStep + 1, 'forward');
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      showStep(currentStep - 1, 'backward');
    });
  }

  form.addEventListener('submit', function (event) {
    if (currentStep < TOTAL_STEPS) {
      event.preventDefault();
      if (validateStep(currentStep, true)) {
        showStep(currentStep + 1, 'forward');
      }
      return;
    }

    if (card && card.checked && upload && fileInput) {
      upload.classList.remove('is-collapsed');
      fileInput.setAttribute('name', 'student_card');
    }

    for (var s = 1; s <= TOTAL_STEPS; s++) {
      if (!validateStep(s, s === currentStep)) {
        event.preventDefault();
        showStep(s, s > currentStep ? 'forward' : 'backward');
        scrollToFirstError();
        return;
      }
    }

    if (!validateSchoolEmail(true)) {
      event.preventDefault();
      showStep(2, 'backward');
      scrollToFirstError();
      if (emailInput) emailInput.focus();
      return;
    }

    if (submitBtn && submitBtn.disabled) {
      event.preventDefault();
      syncFormState();
      scrollToFirstError();
    }
  });

  if (emailInput) {
    emailInput.addEventListener('blur', function () {
      if (currentStep === 2 && school && school.checked) validateSchoolEmail(true);
      syncFormState();
    });
    emailInput.addEventListener('input', function () {
      if (currentStep === 2 && school && school.checked) validateSchoolEmail(false);
      else clearClientEmailError();
      syncFormState();
    });
  }

  ['input', 'change'].forEach(function (evt) {
    form.addEventListener(evt, function (e) {
      if (e.target.matches('input, select, textarea')) syncFormState();
    });
  });

  if (passwordInput) {
    passwordInput.addEventListener('input', function () {
      syncPasswordHint();
      syncFormState();
    });
    syncPasswordHint();
  }

  if (school && card) {
    school.addEventListener('change', syncMethod);
    card.addEventListener('change', syncMethod);
    syncMethod();
  }

  var initialStep = typeof window.__emspRegisterInitialStep === 'number'
    ? window.__emspRegisterInitialStep
    : 1;
  showStep(Math.min(Math.max(initialStep, 1), TOTAL_STEPS));
  syncFormState();
}());
