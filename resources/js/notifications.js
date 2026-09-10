const bell = document.getElementById('notification-bell');
if (bell) {
    let pending = false, expired = false;
    async function refreshCount() {
        if (document.hidden || pending || expired) return;
        pending = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(bell.dataset.countUrl, {
                headers: {Accept:'application/json'}, cache:'no-store', signal:controller.signal,
            });
            if (response.status === 401 || response.status === 419) { expired = true; return; }
            if (!response.ok) return;
            const data = await response.json();
            if (!Number.isInteger(data.unread) || data.unread < 0) return;
            const badge = document.getElementById('notification-badge');
            badge.textContent = data.unread > 99 ? '99+' : String(data.unread);
            badge.hidden = data.unread === 0;
            bell.setAttribute('aria-label', 'แจ้งเตือน ยังไม่อ่าน ' + data.unread + ' รายการ');
        } catch {
            // Keep the last known count during temporary connectivity failures.
        } finally {
            clearTimeout(timeout); pending = false;
        }
    }
    setInterval(refreshCount, 30000);
    document.addEventListener('visibilitychange', refreshCount);
    window.addEventListener('pageshow', refreshCount);
}
