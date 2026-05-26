document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('emailInput');
    const passInput = document.getElementById('passInput');
    const togglePass = document.getElementById('togglePass');
    const emailError = document.getElementById('emailError');
    const passError = document.getElementById('passError');
    const phpAlert = document.getElementById('phpAlert');

    const gmailRegex = /^[a-zA-Z0-9._%+\-]+@gmail\.com$/i;

    function setError(input, errorBox, message) {
        if (errorBox) errorBox.textContent = message;
        if (input) input.classList.add('input-error');
    }

    function clearError(input, errorBox) {
        if (errorBox) errorBox.textContent = '';
        if (input) input.classList.remove('input-error');
    }

    function validateEmail() {
        const email = emailInput.value.trim().toLowerCase();

        if (email === '') {
            setError(emailInput, emailError, 'Please enter your email address.');
            return false;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setError(emailInput, emailError, 'Invalid email format.');
            return false;
        }

        if (!gmailRegex.test(email)) {
            setError(emailInput, emailError, 'Yahoo, Hotmail, Outlook and other email providers are not accepted. Only Gmail is accepted.');
            return false;
        }

        clearError(emailInput, emailError);
        return true;
    }

    function validatePassword() {
        const password = passInput.value.trim();

        if (password === '') {
            setError(passInput, passError, 'Please enter your password.');
            return false;
        }

        clearError(passInput, passError);
        return true;
    }

    if (togglePass && passInput) {
        togglePass.addEventListener('click', () => {
            const isHidden = passInput.type === 'password';
            passInput.type = isHidden ? 'text' : 'password';
            togglePass.textContent = isHidden ? '🙈' : '👁';
        });
    }

    if (emailInput) {
        emailInput.addEventListener('input', validateEmail);
        emailInput.addEventListener('blur', validateEmail);
    }

    if (passInput) {
        passInput.addEventListener('input', validatePassword);
        passInput.addEventListener('blur', validatePassword);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            const emailValid = validateEmail();
            const passwordValid = validatePassword();

            if (!emailValid || !passwordValid) {
                e.preventDefault();
            }
        });
    }

    if (phpAlert) {
        setTimeout(() => {
            phpAlert.style.opacity = '0';
            phpAlert.style.transition = '0.4s ease';
            setTimeout(() => {
                phpAlert.style.display = 'none';
            }, 400);
        }, 5000);
    }
});