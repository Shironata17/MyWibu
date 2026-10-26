(() => {
    const storageKey = 'mywibu-theme';
    const choices = ['system', 'light', 'dark'];
    const labels = {
        system: ['◐', 'Tema otomatis', 'Tema saat ini: mengikuti pengaturan perangkat. Klik untuk tema terang.'],
        light: ['☀', 'Tema terang', 'Tema saat ini: terang. Klik untuk tema gelap.'],
        dark: ['☾', 'Tema gelap', 'Tema saat ini: gelap. Klik untuk mengikuti perangkat.']
    };
    let theme = 'system';

    try {
        const storedTheme = localStorage.getItem(storageKey);
        if (choices.includes(storedTheme)) {
            theme = storedTheme;
        }
    } catch {
        theme = 'system';
    }

    document.documentElement.dataset.theme = theme;

    function addThemeControl() {
        const host = document.querySelector('.app-header, .auth-form-wrap');
        if (!host) {
            return;
        }

        const button = document.createElement('button');
        const icon = document.createElement('span');
        button.type = 'button';
        button.className = 'theme-toggle';
        button.append(icon, document.createTextNode(' Tema'));
        button.addEventListener('click', () => {
            theme = choices[(choices.indexOf(theme) + 1) % choices.length];
            document.documentElement.dataset.theme = theme;
            try {
                localStorage.setItem(storageKey, theme);
            } catch {
                // Keep the selected theme for this page even when storage is unavailable.
            }
            updateThemeControl(button, icon);
        });
        host.append(button);
        updateThemeControl(button, icon);
    }

    function updateThemeControl(button, icon) {
        const [symbol, , description] = labels[theme];
        icon.textContent = symbol;
        button.setAttribute('aria-label', description);
        button.title = description;

        const themeColor = document.querySelector('meta[name="theme-color"]');
        if (themeColor) {
            themeColor.content = getComputedStyle(document.documentElement).getPropertyValue('--canvas').trim();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addThemeControl, { once: true });
    } else {
        addThemeControl();
    }
})();
