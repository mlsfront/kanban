(function () {
    try {
        var saved = localStorage.getItem('kanban-theme');
        var preferred = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.dataset.theme = preferred;
    } catch (error) {
        document.documentElement.dataset.theme = 'light';
    }
})();
