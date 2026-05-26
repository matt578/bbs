document.addEventListener('DOMContentLoaded', function () {
    const signupForm = document.getElementById('signupForm');
    const submitBtn = document.getElementById('submitBtn');
    const phpAlert = document.getElementById('phpAlert');

    const nameInput = document.getElementById('nameInput');
    const emailInput = document.getElementById('emailInput');
    const passInput = document.getElementById('passInput');
    const confirmInput = document.getElementById('confirmInput');

    const togglePass1 = document.getElementById('togglePass1');
    const togglePass2 = document.getElementById('togglePass2');

    const nameError = document.getElementById('nameError');
    const emailError = document.getElementById('emailError');
    const passError = document.getElementById('passError');
    const confirmError = document.getElementById('confirmError');

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

    function attachToggle(button, input) {
        if (!button || !input) return;

        button.addEventListener('click', function (e) {
            e.preventDefault();

            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = '🙈';
            } else {
                input.type = 'password';
                button.textContent = '👁';
            }
        });
    }

    attachToggle(togglePass1, passInput);
    attachToggle(togglePass2, confirmInput);

    if (nameInput) {
        nameInput.addEventListener('input', function () {
            clearError(nameError, nameInput);
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

    if (confirmInput) {
        confirmInput.addEventListener('input', function () {
            clearError(confirmError, confirmInput);
        });
    }

    if (signupForm) {
        signupForm.addEventListener('submit', function (e) {
            let valid = true;

            const name = nameInput ? nameInput.value.trim() : '';
            const email = emailInput ? emailInput.value.trim() : '';
            const password = passInput ? passInput.value : '';
            const confirmPassword = confirmInput ? confirmInput.value : '';

            clearError(nameError, nameInput);
            clearError(emailError, emailInput);
            clearError(passError, passInput);
            clearError(confirmError, confirmInput);

            if (name === '') {
                showError(nameError, nameInput, 'Full name is required.');
                valid = false;
            } else if (name.length < 2) {
                showError(nameError, nameInput, 'Full name must be at least 2 characters.');
                valid = false;
            }

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
            } else if (password.length < 6) {
                showError(passError, passInput, 'Password must be at least 6 characters.');
                valid = false;
            }

            if (confirmPassword === '') {
                showError(confirmError, confirmInput, 'Please confirm your password.');
                valid = false;
            } else if (password !== confirmPassword) {
                showError(confirmError, confirmInput, 'Passwords do not match.');
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