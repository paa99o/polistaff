<script>
    (() => {
        const root = document.documentElement;
        let preference = root.dataset.themePreference || 'light';

        try {
            // Keep the visual theme stable while moving between public/authenticated
            // pages. The server preference remains the fallback for a new browser.
            const storedPreference = localStorage.getItem('polistaff-theme-preference');

            if (['light', 'dark'].includes(storedPreference)) {
                preference = storedPreference;
            }

            localStorage.setItem('polistaff-theme-preference', preference);
            root.dataset.themePreference = preference;
        } catch (error) {
            // Browser privacy settings may disable local storage.
        }

        root.dataset.theme = preference;
        root.dataset.bsTheme = preference;
    })();
</script>
