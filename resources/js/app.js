const appShell = document.querySelector('.app-shell');
const hasSeenAuthenticatedMotion = sessionStorage.getItem('polistaff-auth-motion-seen') === 'true';

if (!appShell || !hasSeenAuthenticatedMotion) {
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

if (appShell && hasSeenAuthenticatedMotion) {
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
