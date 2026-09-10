const root = document.getElementById('gacha-room');
if (root) {
    const config = JSON.parse(document.getElementById('gacha-settings').textContent);
    const el = name => document.getElementById('gacha-' + name);
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const money = value => '฿' + Number(value).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    let busy = false, pending = null, animation = null, sound = false, audio = null, skip = false;
    try { pending = sessionStorage.getItem(config.storageKey); } catch {}
    function remember(value) {
        pending = value;
        try { value ? sessionStorage.setItem(config.storageKey, value) : sessionStorage.removeItem(config.storageKey); } catch {}
    }
    function tone(win = false) {
        if (!sound) return;
        try {
            audio ??= new (window.AudioContext || window.webkitAudioContext)();
            audio.resume().catch(() => {});
            const osc = audio.createOscillator(), gain = audio.createGain(), now = audio.currentTime;
            osc.type = 'sine';
            osc.frequency.setValueAtTime(win ? 660 : 180, now);
            osc.frequency.exponentialRampToValueAtTime(win ? 1320 : 420, now + .25);
            gain.gain.setValueAtTime(.035, now);
            gain.gain.exponentialRampToValueAtTime(.001, now + .4);
            osc.connect(gain); gain.connect(audio.destination); osc.start(now); osc.stop(now + .4);
        } catch {}
    }
    function controls() {
        if (!el('spin')) return;
        const unavailable = !config.active || !config.rewards.length;
        el('spin').disabled = busy || (!pending && unavailable);
        el('spin').textContent = pending ? 'ตรวจสอบผลรายการเดิม' : unavailable ? 'กล่องนี้ยังไม่พร้อมสุ่ม' :
            config.balance < config.price ? 'เงินไม่พอ · ไปเติมเงิน' : 'สุ่ม 1 ครั้ง · ' + money(config.price);
        el('again').disabled = unavailable;
        el('again').textContent = config.balance < config.price ? 'ไปเติมเงิน' : 'สุ่มอีกครั้ง · ' + money(config.price);
    }
    function art(container, reward) {
        container.replaceChildren();
        if (reward.image) {
            const image = document.createElement('img');
            image.src = reward.image; image.alt = reward.title;
            image.addEventListener('error', () => { container.textContent = reward.type === 'credit' ? '💎' : '🎮'; }, {once: true});
            container.append(image);
        } else container.textContent = reward.type === 'credit' ? '💎' : '🎮';
    }
    function card(reward) {
        const node = document.createElement('div'), visual = document.createElement('div'), title = document.createElement('p');
        node.className = 'gacha-card ' + (reward.type === 'game_account' ? 'gacha-gold' : '');
        visual.className = 'gacha-card-art'; title.textContent = reward.title;
        art(visual, reward); node.append(visual, title); return node;
    }
    async function scene(data) {
        const winner = {title: data.account_title || 'เครดิต ' + money(data.credit_amount), type: data.reward_type, image: data.reward_image};
        if (!reduced.matches) {
            el('stage').classList.add('is-opening');
            await new Promise(resolve => setTimeout(resolve, 850));
            el('stage').classList.remove('is-opening');
        }
        el('chest').hidden = true; el('reel').hidden = false;
        const track = el('track');
        track.replaceChildren(); track.style.transform = 'translateX(0)';
        // This strip is visual only; the server's saved reward occupies the landing slot.
        for (let i = 0; i < 34; i++) track.append(card(i === 28 ? winner : config.rewards[i % config.rewards.length] || winner));
        const landing = track.children[28];
        const offset = el('reel').clientWidth / 2 - landing.offsetLeft - landing.offsetWidth / 2;
        if (!skip && !reduced.matches) {
            animation = track.animate([{transform:'translateX(0)'}, {transform:'translateX(' + offset + 'px)'}],
                {duration: 3800, easing:'cubic-bezier(.12,.65,.14,1)', fill:'forwards'});
            await animation.finished.catch(() => {});
            animation.cancel(); animation = null;
        }
        track.style.transform = 'translateX(' + offset + 'px)';
        landing.classList.add('is-winner');
        if (!skip && !reduced.matches) await new Promise(resolve => setTimeout(resolve, 450));
        el('result').classList.toggle('gacha-gold', data.reward_type === 'game_account');
        art(el('result-art'), winner);
        el('result-text').textContent = data.result;
        el('purchase').hidden = !data.purchase_url;
        if (data.purchase_url) el('purchase').href = data.purchase_url;
        el('result').showModal(); tone(true);
    }
    async function spin() {
        if (busy) return;
        if (!pending && config.balance < config.price) { location.href = config.walletUrl; return; }
        if (!pending && (!config.active || !config.rewards.length)) return;
        busy = true; skip = false; el('error').hidden = true;
        el('status').textContent = 'กำลังตรวจสอบและบันทึกรางวัล…';
        try {
            if (!pending) {
                const bytes = crypto.getRandomValues(new Uint8Array(16));
                bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
                const hex = [...bytes].map(b => b.toString(16).padStart(2, '0')).join('');
                remember(hex.slice(0,8)+'-'+hex.slice(8,12)+'-'+hex.slice(12,16)+'-'+hex.slice(16,20)+'-'+hex.slice(20));
            }
            controls(); tone();
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 20000);
            let response, data;
            try {
                response = await fetch(config.url, {
                    method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':config.csrf},
                    body:JSON.stringify({request_id:pending}), signal:controller.signal
                });
                data = await response.json();
            } finally { clearTimeout(timeout); }
            if (!response.ok || !data.success) {
                if (response.status === 422) remember(null);
                throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'ไม่สามารถสุ่มได้');
            }
            config.balance = data.new_balance;
            el('balance').textContent = money(config.balance);
            document.querySelectorAll('a[href="' + config.walletUrl + '"]').forEach(a => {
                if (!a.contains(el('balance')) && a.textContent.includes('Wallet')) a.textContent = 'Wallet ' + money(config.balance);
            });
            el('status').textContent = 'บันทึกรางวัลแล้ว กำลังเปิดกล่องของคุณ…';
            el('skip').hidden = false;
            await scene(data);
            remember(null);
            if (data.reward_type === 'game_account') config.rewards = config.rewards.filter(r => r.id !== data.item_id);
            const row = document.createElement('div'), label = document.createElement('p');
            row.className = 'py-3 text-sm'; label.textContent = '#' + data.spin_id + ' · ' + data.result;
            row.append(label);
            if (data.purchase_url) {
                const link = document.createElement('a'); link.href = data.purchase_url;
                link.textContent = 'ดูข้อมูลไอดี'; link.className = 'text-violet-300 underline'; row.append(link);
            }
            el('history').prepend(row);
        } catch (error) {
            el('error').textContent = pending ? 'ยังยืนยันผลไม่ได้ กดตรวจสอบผลรายการเดิมโดยไม่หักเงินซ้ำ หากยังไม่ได้ให้รีเฟรชหรือเข้าสู่ระบบอีกครั้ง' : error.message;
            el('error').hidden = false;
        } finally {
            busy = false; el('skip').hidden = true; el('stage').classList.remove('is-opening');
            el('status').textContent = 'ผลสุ่มเก็บไว้ในประวัติของคุณ'; controls();
        }
    }
    el('sound').addEventListener('click', () => {
        sound = !sound; el('sound').textContent = sound ? 'เสียง: เปิด' : 'เสียง: ปิด';
        el('sound').setAttribute('aria-pressed', String(sound)); if (sound) tone();
    });
    el('spin')?.addEventListener('click', spin);
    el('skip')?.addEventListener('click', () => { skip = true; animation?.finish(); });
    el('close').addEventListener('click', () => { el('result').close(); location.reload(); });
    el('result').addEventListener('cancel', event => { event.preventDefault(); location.reload(); });
    el('again').addEventListener('click', () => {
        el('result').close(); el('reel').hidden = true; el('chest').hidden = false; spin();
    });
    controls();
}
