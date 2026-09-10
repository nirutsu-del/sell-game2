document.querySelectorAll('[data-image-preview]').forEach(input => {
    const preview = document.getElementById(input.dataset.imagePreview);
    const original = preview.getAttribute('src');
    let url = null;
    input.addEventListener('change', () => {
        if (url) URL.revokeObjectURL(url);
        url = null;
        const file = input.files[0];
        if (file && ['image/png','image/jpeg','image/webp'].includes(file.type)) {
            url = URL.createObjectURL(file); preview.src = url; preview.hidden = false;
        } else {
            if (original) preview.src = original; else preview.removeAttribute('src');
            preview.hidden = !original;
        }
    });
});
const bannerTrack = document.getElementById('store-banner-track');
const floatingContact = document.getElementById('floating-store-contact');
if (floatingContact) {
    document.addEventListener('click', event => {
        if (!floatingContact.contains(event.target)) floatingContact.open = false;
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && floatingContact.open) {
            floatingContact.open = false;
            floatingContact.querySelector('summary').focus();
        }
    });
}
if (bannerTrack) {
    const buttons = [...document.querySelectorAll('[data-banner-direction]')];
    function updateButtons() {
        buttons.forEach(button => {
            button.disabled = Number(button.dataset.bannerDirection) < 0 ? bannerTrack.scrollLeft <= 1 :
                bannerTrack.scrollLeft + bannerTrack.clientWidth >= bannerTrack.scrollWidth - 2;
        });
    }
    buttons.forEach(button => button.addEventListener('click', () => {
        bannerTrack.scrollBy({
            left: Number(button.dataset.bannerDirection) * (bannerTrack.clientWidth + 16),
            behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
        });
    }));
    bannerTrack.addEventListener('scroll', updateButtons, {passive:true});
    window.addEventListener('resize', updateButtons);
    updateButtons();
}
