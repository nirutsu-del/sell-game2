import './bootstrap';
import './gacha';
import './catalog';
import './notifications';
import './store-settings';
import './collection';

document.querySelectorAll('[data-open-dialog]').forEach(button => {
    button.addEventListener('click', () => document.getElementById(button.dataset.openDialog)?.showModal());
});
document.querySelectorAll('[data-close-dialog]').forEach(button => {
    button.addEventListener('click', () => button.closest('dialog').close());
});
document.querySelectorAll('[data-purchase-form]').forEach(form => {
    form.addEventListener('submit', event => {
        if (form.dataset.submitting) { event.preventDefault(); return; }
        form.dataset.submitting = 'true';
        form.querySelector('button').disabled = true;
        form.querySelector('button').textContent = 'กำลังดำเนินการ…';
    });
});
window.addEventListener('pageshow', () => document.querySelectorAll('[data-purchase-form]').forEach(form => {
    delete form.dataset.submitting;
    form.querySelector('button').disabled = false;
    form.querySelector('button').textContent = 'ยืนยันซื้อด้วย Wallet';
}));

const adminMenu = document.querySelector('.vault-admin-menu');
if (adminMenu) {
    const desktop = window.matchMedia('(min-width: 1100px)');
    const syncMenu = () => { adminMenu.open = desktop.matches; };
    desktop.addEventListener('change', syncMenu);
    syncMenu();
}

document.querySelectorAll('[data-copy-credential]').forEach(button => {
    button.addEventListener('click', async () => {
        const value = button.closest('.vault-credential')?.querySelector('[data-credential-value]')?.textContent ?? '';
        const status = document.getElementById('credential-status');
        try {
            await navigator.clipboard.writeText(value);
            button.textContent = 'คัดลอกแล้ว';
            if (status) status.textContent = 'คัดลอกข้อมูลแล้ว';
            window.setTimeout(() => { button.textContent = 'คัดลอก'; }, 1800);
        } catch {
            if (status) status.textContent = 'คัดลอกอัตโนมัติไม่ได้ กรุณาเลือกข้อความแล้วคัดลอก';
        }
    });
});
