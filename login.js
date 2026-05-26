document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const phpAlert = document.getElementById('phpAlert');

    const emailInput = document.getElementById('emailInput');
    const passInput = document.getElementById('passInput');
    const togglePass = document.getElementById('togglePass');

    const emailError = document.getElementById('emailError');
    const passError = document.getElementById('passError');

    function showError(el, input, message) {
        if (el) {
            el.textContent = message;
            el.style.display = 'block';
            el.style.visibility = 'visible';
            el.style.opacity = '1';
        }
        if (input) {
            input.classList.add('input-error');
        }
    }

    function clearError(el, input) {
        if (el) {
            el.textContent = '';
            el.style.display = 'block';
            el.style.visibility = 'visible';
            el.style.opacity = '1';
        }
        if (input) {
            input.classList.remove('input-error');
        }
    }

    function isValidGmail(email) {
        return /^[a-zA-Z0-9._%+\-]+@gmail\.com$/i.test(email);
    }

    if (togglePass && passInput) {
        togglePass.addEventListener('click', function (e) {
            e.preventDefault();

            if (passInput.type === 'password') {
                passInput.type = 'text';
                togglePass.textContent = '🙈';
            } else {
                passInput.type = 'password';
                togglePass.textContent = '👁';
            }
        });
    }

    if (emailInput) {
        emailInput.addEventListener('input', function () {
            clearError(emailError, emailInput);
        });
    }

    if (passInput) {
        passInput.addEventListener('input', function () {
            clearError(passError, passInput);
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            let valid = true;
            const email = emailInput ? emailInput.value.trim() : '';
            const password = passInput ? passInput.value : '';

            clearError(emailError, emailInput);
            clearError(passError, passInput);

            if (email === '') {
                showError(emailError, emailInput, 'Email is required.');
                valid = false;
            } else if (!isValidGmail(email)) {
                showError(emailError, emailInput, 'Invalid account. Only Gmail is allowed.');
                valid = false;
            }

            if (password === '') {
                showError(passError, passInput, 'Password is required.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('is-loading');
            }
        });
    }

    if (phpAlert) {
        setTimeout(function () {
            phpAlert.style.transition = 'opacity 0.4s ease';
            phpAlert.style.opacity = '0';
            setTimeout(function () {
                if (phpAlert.parentNode) {
                    phpAlert.parentNode.removeChild(phpAlert);
                }
            }, 400);
        }, 3000);
    }
});