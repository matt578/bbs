document.addEventListener('DOMContentLoaded', function () {
    const topBadge = document.getElementById('topBadge');
    const logoRing = document.getElementById('logoRing');
    const brandName = document.getElementById('brandName');
    const brandWords = brandName ? brandName.querySelectorAll('.brand-word') : [];
    const brandTagline = document.getElementById('brandTagline');
    const dividerLine = document.getElementById('dividerLine');
    const roleSection = document.getElementById('roleSection');
    const landingFooter = document.getElementById('landingFooter');
    const particles = document.getElementById('particles');

    function safeAddVisible(el, delay) {
        if (!el) return;
        setTimeout(function () {
            el.classList.add('visible');
        }, delay);
    }

    safeAddVisible(topBadge, 100);
    safeAddVisible(logoRing, 300);

    if (brandWords.length > 0) {
        brandWords.forEach(function (word, index) {
            setTimeout(function () {
                word.classList.add('visible');
            }, 500 + (index * 180));
        });
    }

    safeAddVisible(brandTagline, 1100);
    safeAddVisible(dividerLine, 1300);
    safeAddVisible(roleSection, 1500);
    safeAddVisible(landingFooter, 1750);

    if (particles) {
        for (let i = 0; i < 35; i++) {
            const particle = document.createElement('span');
            particle.className = 'particle';

            const size = Math.random() * 3 + 1;
            const left = Math.random() * 100;
            const delay = Math.random() * 10;
            const duration = Math.random() * 8 + 8;

            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            particle.style.left = left + '%';
            particle.style.bottom = '-20px';
            particle.style.animationDelay = delay + 's';
            particle.style.animationDuration = duration + 's';

            particles.appendChild(particle);
        }
    }
});