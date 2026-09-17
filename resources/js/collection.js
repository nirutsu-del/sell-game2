const allowedThemes = ['fire', 'ice', 'galaxy'];
const themeKey = user => `mizuki:collection-theme:${user}`;
function savedTheme(user) {
    try {
        const value = localStorage.getItem(themeKey(user));
        return allowedThemes.includes(value) ? value : 'ice';
    } catch { return 'ice'; }
}
const room = document.querySelector('[data-collection-theme]');
if (room) {
    const applyTheme = theme => {
        room.dataset.theme = theme;
        room.querySelectorAll('[data-theme-choice]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.themeChoice === theme));
        });
    };
    applyTheme(savedTheme(room.dataset.themeUser));
    room.querySelectorAll('[data-theme-choice]').forEach(button => button.addEventListener('click', () => {
        const theme = button.dataset.themeChoice;
        if (!allowedThemes.includes(theme)) return;
        applyTheme(theme);
        try { localStorage.setItem(themeKey(room.dataset.themeUser), theme); } catch { /* Theme still works without storage. */ }
    }));
}
const reveal = document.querySelector('[data-acquisition-reveal]');
if (reveal) {
    reveal.dataset.theme = savedTheme(reveal.dataset.themeUser);
    const seenKey = `mizuki:reveal:${reveal.dataset.themeUser}:${reveal.dataset.purchaseId}`;
    const show = () => {
        if (reveal.open) return;
        reveal.showModal();
        try { sessionStorage.setItem(seenKey, 'seen'); } catch { /* Server flash limits automatic replay. */ }
    };
    document.querySelector('[data-replay-reveal]')?.addEventListener('click', show);
    let seen = false;
    try { seen = sessionStorage.getItem(seenKey) === 'seen'; } catch { /* Storage is optional. */ }
    if (reveal.dataset.autoReveal === 'true' && !seen) show();
}
