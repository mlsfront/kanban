(function () {
    const storageKey = 'kanban-theme';
    const savedTheme = localStorage.getItem(storageKey);
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');
    document.documentElement.dataset.theme = initialTheme;

    function updateButton(button) {
        const dark = document.documentElement.dataset.theme === 'dark';
        button.textContent = dark ? '☀️' : '🌙';
        button.setAttribute('title', dark ? 'Ativar tema claro' : 'Ativar tema escuro');
        button.setAttribute('aria-label', dark ? 'Ativar tema claro' : 'Ativar tema escuro');
    }

    function addToggle() {
        if (document.querySelector('.theme-toggle')) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'theme-toggle';
        button.addEventListener('click', function () {
            const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            localStorage.setItem(storageKey, next);
            updateButton(button);
        });
        updateButton(button);
        document.body.appendChild(button);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addToggle, { once: true });
    } else {
        addToggle();
    }
})();
