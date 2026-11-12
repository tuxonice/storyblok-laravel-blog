// Theme toggle. The saved theme is applied before first paint by the inline script in
// the layout's <head>; this only handles clicks and keeps the button label in sync.
const root = document.documentElement;
const button = document.querySelector('[data-theme-toggle]');

const current = () => (root.getAttribute('data-bs-theme') === 'light' ? 'light' : 'dark');

const sync = () => {
    button?.setAttribute('aria-label', current() === 'light' ? 'Switch to dark theme' : 'Switch to light theme');
};

button?.addEventListener('click', () => {
    const next = current() === 'light' ? 'dark' : 'light';
    root.setAttribute('data-bs-theme', next);

    try {
        localStorage.setItem('theme', next);
    } catch (e) {
        // Private mode or blocked storage: the choice just won't be remembered.
    }

    sync();
});

sync();
