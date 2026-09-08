const root = document.documentElement;
const appShell = document.querySelector('.app-shell');
const reduceMotion = root.dataset.reduceMotion === 'true';
const hasSeenAuthenticatedMotion = sessionStorage.getItem('polistaff-auth-motion-seen') === 'true';

const updateThemeControls = (preference) => {
    document.querySelectorAll('[data-theme-option]').forEach((button) => {
        const active = button.dataset.themeOption === preference;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
};

const applyThemePreference = (preference) => {
    root.dataset.themePreference = preference;
    root.dataset.theme = preference;
    root.dataset.bsTheme = preference;

    const themeColor = document.querySelector('meta[name="theme-color"]');
    themeColor?.setAttribute('content', preference === 'dark' ? '#18263d' : '#eee5d8');

    try {
        localStorage.setItem('polistaff-theme-preference', preference);
    } catch (error) {
        // Browser privacy settings may disable local storage.
    }

    updateThemeControls(preference);
};

if (!reduceMotion && (!appShell || !hasSeenAuthenticatedMotion)) {
    document.documentElement.classList.add('motion-ready');
}

const revealSelectors = [
    '.page-intro',
    '.content-wrap > section',
    '.content-wrap > .card',
    '.content-wrap > .row',
    '.auth-card',
    '.public-hero > *',
    '.public-section-header',
    '.public-feature',
];

const revealItems = document.querySelectorAll(revealSelectors.join(','));

revealItems.forEach((item, index) => {
    item.dataset.reveal = '';
    item.style.setProperty('--reveal-delay', `${Math.min(index % 4, 3) * 60}ms`);
});

if (reduceMotion || (appShell && hasSeenAuthenticatedMotion)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
} else if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, {
        rootMargin: '0px 0px -32px',
        threshold: 0.08,
    });

    revealItems.forEach((item) => revealObserver.observe(item));
} else {
    revealItems.forEach((item) => item.classList.add('is-visible'));
}

if (appShell) {
    sessionStorage.setItem('polistaff-auth-motion-seen', 'true');
}

updateThemeControls(root.dataset.themePreference || 'light');

document.querySelectorAll('[data-theme-option]').forEach((button) => {
    button.addEventListener('click', () => applyThemePreference(button.dataset.themeOption));
});

document.querySelectorAll('input[name="theme_preference"]').forEach((input) => {
    input.addEventListener('change', () => {
        if (input.checked) {
            applyThemePreference(input.value);
        }
    });
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');

    if (!form || window.confirm(form.dataset.confirm || 'Teruskan tindakan ini?')) {
        return;
    }

    event.preventDefault();
});
