document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('signupForm');
    const nameInput = document.getElementById('nameInput');
    const emailInput = document.getElementById('emailInput');
    const passInput = document.getElementById('passInput');
    const confirmPassInput = document.getElementById('confirmPassInput');

    const togglePass1 = document.getElementById('togglePass1');
    const togglePass2 = document.getElementById('togglePass2');

    const nameError = document.getElementById('nameError');
    const emailError = document.getElementById('emailError');
    const passError = document.getElementById('passError');
    const confirmError = document.getElementById('confirmError');

    const submitBtn = document.getElementById('submitBtn');
    const phpAlert = document.getElementById('phpAlert');

    const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
    const blockedDomains = [
        'yahoo.com', 'ymail.com', 'rocketmail.com',
        'hotmail.com', 'outlook.com', 'live.com', 'msn.com',
        'icloud.com', 'me.com', 'mac.com',
        'aol.com', 'protonmail.com', 'zoho.com', 'mail.com'
    ];

    function setError(element, message) {
        if (element) {
            element.textContent = message;
        }
    }

    function clearError(element) {
        if (element) {
            element.textContent = '';
        }
    }

    function getDomain(email) {
        const parts = email.toLowerCase().split('@');
        return parts.length === 2 ? parts[1] : '';
    }

    function validateName() {
        const name = nameInput.value.trim();

        if (name === '') {
            setError(nameError, 'Please enter your full name.');
            return false;
        }

        if (name.length < 2) {
            setError(nameError, 'Name must be at least 2 characters.');
            return false;
        }

        clearError(nameError);
        return true;
    }

    function validateEmail() {
        const email = emailInput.value.trim().toLowerCase();

        if (email === '') {
            setError(emailError, 'Please enter your email address.');
            return false;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setError(emailError, 'Invalid email format. Please use a valid Gmail address.');
            return false;
        }

        const domain = getDomain(email);

        if (blockedDomains.includes(domain) || !gmailRegex.test(email)) {
            setError(emailError, 'Yahoo, Hotmail, Outlook and other email providers are not accepted. Only Gmail addresses are allowed.');
            return false;
        }

        clearError(emailError);
        return true;
    }

    function validatePassword() {
        const password = passInput.value;

        if (password === '') {
            setError(passError, 'Please enter your password.');
            return false;
        }

        if (password.length < 6) {
            setError(passError, 'Password must be at least 6 characters.');
            return false;
        }

        clearError(passError);
        return true;
    }

    function validateConfirmPassword() {
        const password = passInput.value;
        const confirmPassword = confirmPassInput.value;

        if (confirmPassword === '') {
            setError(confirmError, 'Please confirm your password.');
            return false;
        }

        if (password !== confirmPassword) {
            setError(confirmError, 'Passwords do not match.');
            return false;
        }

        clearError(confirmError);
        return true;
    }

    function attachToggle(button, input) {
        if (!button || !input) return;

        button.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? '🙈' : '👁';
        });
    }

    attachToggle(togglePass1, passInput);
    attachToggle(togglePass2, confirmPassInput);

    nameInput.addEventListener('input', validateName);
    emailInput.addEventListener('input', validateEmail);
    passInput.addEventListener('input', () => {
        validatePassword();
        if (confirmPassInput.value !== '') {
            validateConfirmPassword();
        }
    });
    confirmPassInput.addEventListener('input', validateConfirmPassword);

    nameInput.addEventListener('blur', validateName);
    emailInput.addEventListener('blur', validateEmail);
    passInput.addEventListener('blur', validatePassword);
    confirmPassInput.addEventListener('blur', validateConfirmPassword);

    form.addEventListener('submit', (e) => {
        const isNameValid = validateName();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmValid = validateConfirmPassword();

        if (!isNameValid || !isEmailValid || !isPasswordValid || !isConfirmValid) {
            e.preventDefault();
            return;
        }

        if (submitBtn) {
            submitBtn.classList.add('is-loading');
            submitBtn.disabled = true;
        }
    });

    if (phpAlert) {
        setTimeout(() => {
            phpAlert.style.transition = 'opacity 0.4s ease';
            phpAlert.style.opacity = '0';
            setTimeout(() => {
                phpAlert.style.display = 'none';
            }, 400);
        }, 4500);
    }
});