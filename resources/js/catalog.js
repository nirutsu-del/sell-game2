const orderForm = document.getElementById('product-order-form');
if (orderForm) {
    const quantity = document.getElementById('product-quantity'), total = document.getElementById('product-total');
    const dialog = document.getElementById('product-confirm');
    const selected = () => orderForm.querySelector('input[name="variant_id"]:checked');
    const format = cents => '฿' + (cents / 100).toLocaleString('th-TH', {minimumFractionDigits:2});
    function update() {
        const variant = selected();
        quantity.max = variant?.dataset.stock ? Math.min(100, Number(variant.dataset.stock)) : 100;
        total.textContent = variant ? format(Math.round(Number(variant.dataset.price)*100)*Number(quantity.value)) : 'เลือกแพ็กเกจ';
    }
    orderForm.addEventListener('input', update); update();
    let confirmed = false;
    orderForm.addEventListener('submit', event => {
        if (confirmed) return;
        event.preventDefault();
        document.getElementById('product-summary').textContent = selected().dataset.name + ' × ' + quantity.value + ' · ' + total.textContent;
        document.getElementById('product-recipient').textContent = 'ผู้รับ: ' + orderForm.elements.recipient.value;
        dialog.showModal();
    });
    document.getElementById('product-cancel').addEventListener('click', () => dialog.close());
    document.getElementById('product-pay').addEventListener('click', event => {
        if (!orderForm.reportValidity()) { dialog.close(); return; }
        confirmed = true; event.currentTarget.disabled = true; event.currentTarget.textContent = 'กำลังบันทึก…';
        orderForm.requestSubmit();
    });
}
const editor = document.getElementById('variant-editor');
if (editor) {
    document.getElementById('add-variant').addEventListener('click', () => {
        const index = editor.children.length;
        if (index >= 30) return;
        const row = editor.firstElementChild.cloneNode(true);
        row.querySelectorAll('input').forEach(input => {
            input.name = input.name.replace(/variants\[\d+\]/, 'variants[' + index + ']');
            if (input.type === 'checkbox') input.checked = true; else input.value = '';
        });
        editor.append(row); row.querySelector('input:not([type=hidden])').focus();
    });
}
