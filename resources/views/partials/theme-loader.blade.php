<script>
    (() => {
        const root = document.documentElement;
        const authenticated = root.dataset.authenticated === 'true';
        let preference = root.dataset.themePreference || 'light';

        try {
            if (authenticated) {
                localStorage.setItem('polistaff-theme-preference', preference);
            } else {
                preference = localStorage.getItem('polistaff-theme-preference') || 'light';
                preference = ['light', 'dark'].includes(preference) ? preference : 'light';
                root.dataset.themePreference = preference;
            }
        } catch (error) {
            // Browser privacy settings may disable local storage.
        }

        root.dataset.theme = preference;
        root.dataset.bsTheme = preference;
    })();
</script>
