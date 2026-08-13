(function () {
    'use strict';

    var modal = document.getElementById('emspQuickLoginModal');
    if (!modal) {
        return;
    }

    var form = modal.querySelector('.emsp-login-modal-form');
    if (!form) {
        return;
    }

    var emailInput = form.querySelector('[name="email"]');
    var passwordInput = form.querySelector('[name="password"]');
    var submitBtn = form.querySelector('[type="submit"]');
    var errorBox = modal.querySelector('.emsp-login-modal__error');

    function setSubmitLoading(isLoading) {
        if (!submitBtn) {
            return;
        }
        if (isLoading) {
            if (!submitBtn.dataset.emspOriginalHtml) {
                submitBtn.dataset.emspOriginalHtml = submitBtn.innerHTML;
            }
            var loadingText = submitBtn.getAttribute('data-loading-text') || 'Connexion en cours...';
            submitBtn.disabled = true;
            submitBtn.setAttribute('aria-busy', 'true');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + loadingText;
            return;
        }
        submitBtn.disabled = false;
        submitBtn.removeAttribute('aria-busy');
        if (submitBtn.dataset.emspOriginalHtml) {
            submitBtn.innerHTML = submitBtn.dataset.emspOriginalHtml;
        }
    }

    function hideError() {
        if (!errorBox) {
            return;
        }
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    }

    function showError(message) {
        if (!errorBox) {
            return;
        }
        errorBox.textContent = message || 'Identifiants incorrects.';
        errorBox.classList.remove('d-none');
    }

    function resetPasswordField() {
        if (!passwordInput) {
            return;
        }
        passwordInput.value = '';
        passwordInput.type = 'password';
        var toggle = modal.querySelector('#toggle-quick-login-password');
        if (toggle) {
            toggle.setAttribute('aria-pressed', 'false');
            toggle.setAttribute('aria-label', 'Afficher le mot de passe');
            toggle.innerHTML = '<i class="bi bi-eye"></i>';
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        hideError();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        setSubmitLoading(true);

        var body = new FormData(form);

        fetch(form.getAttribute('action') || window.location.href, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return { ok: false, message: 'Réponse serveur invalide. Réessayez.' };
                }).then(function (data) {
                    return { response: response, data: data };
                });
            })
            .then(function (result) {
                var data = result.data || {};
                if (data.ok && data.redirect) {
                    window.location.assign(data.redirect);
                    return;
                }

                setSubmitLoading(false);
                showError(data.message || 'Adresse email ou mot de passe incorrect.');
                resetPasswordField();
                if (passwordInput) {
                    passwordInput.focus();
                }
            })
            .catch(function () {
                setSubmitLoading(false);
                showError('Impossible de contacter le serveur. Vérifiez votre connexion.');
                resetPasswordField();
                if (passwordInput) {
                    passwordInput.focus();
                }
            });
    }, true);

    modal.addEventListener('show.bs.modal', hideError);
    modal.addEventListener('hidden.bs.modal', function () {
        hideError();
        resetPasswordField();
    });
})();
