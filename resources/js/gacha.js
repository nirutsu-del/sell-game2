const root = document.getElementById('gacha-room');
if (root) {
    const config = JSON.parse(document.getElementById('gacha-settings').textContent);
    const el = name => document.getElementById('gacha-' + name);
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const money = value => '฿' + Number(value).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    let busy = false, pending = null, animation = null, sound = false, audio = null, skip = false;
    let mode = 'cards', selectedCard = null;
    const modes = [...document.querySelectorAll('[data-gacha-mode]')];
    const choices = [...document.querySelectorAll('[data-card-index]')];
    let wheelRewards = [];
    function buildWheel(winner = null) {
        wheelRewards = config.rewards.slice(0, 10);
        if (winner && !wheelRewards.some(r => r.id === winner.id)) wheelRewards = [...wheelRewards.slice(0, 9), winner];
        const count = wheelRewards.length || 1;
        const colors = ['#152c43','#1c354a','#18243c','#20384a'];
        el('wheel').classList.toggle('is-dense', count > 6);
        el('wheel').style.background = `conic-gradient(${Array.from({length:count}, (_,i) => {
            const color = wheelRewards[i]?.type === 'game_account' ? '#54402d' : colors[i%colors.length];
            return `#bd9457 ${i*360/count}deg ${i*360/count+.5}deg, ${color} ${i*360/count+.5}deg ${(i+1)*360/count}deg`;
        }).join(',')})`;
        el('wheel').style.transform = 'rotate(0deg)';
        el('wheel').replaceChildren();
        wheelRewards.forEach((reward,i) => {
            const marker = document.createElement('span');
            marker.className = 'gacha-wheel-number';
            const icon = document.createElement('span'), caption = document.createElement('strong');
            icon.className = 'wheel-reward-icon';
            if (reward.type === 'game_account' && reward.image) {
                const image = document.createElement('img'); image.src = reward.image; image.alt = '';
                image.addEventListener('error', () => { icon.textContent = '✦'; }, {once:true}); icon.append(image);
            } else icon.textContent = reward.type === 'credit' ? '฿' : '✦';
            caption.textContent = count > 6 ? String(i+1) : reward.type === 'credit' ? reward.title.replace(/^เครดิต\s*/, '') : 'ไอดีเกม';
            marker.append(icon, caption);
            const angle = (i+.5)*2*Math.PI/count;
            marker.style.left = `${50 + Math.sin(angle)*32}%`; marker.style.top = `${50 - Math.cos(angle)*32}%`;
            el('wheel').append(marker);
        });
    }
    function resetScene() {
        el('reel').hidden = true;
        el('chest').hidden = mode !== 'box';
        el('box-note').hidden = mode !== 'box';
        el('cards-scene').hidden = mode !== 'cards';
        el('wheel-scene').hidden = mode !== 'wheel';
        choices.forEach(button => { button.classList.remove('is-revealed'); button.querySelector('.gacha-choice-front').replaceChildren(); });
        if (mode === 'wheel') buildWheel();
    }
    modes.forEach(button => button.addEventListener('click', () => {
        if (busy) return;
        mode = button.dataset.gachaMode;
        modes.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        resetScene(); controls();
        el('status').textContent = mode === 'cards' ? 'เลือกการ์ดที่ชอบ การเลือกใบไม่เปลี่ยนโอกาสได้รับรางวัล' : 'ดูรายการรางวัลและโอกาสได้รับก่อนเริ่มสุ่ม';
    }));
    choices.forEach(button => button.addEventListener('click', () => {
        if (busy) return;
        selectedCard = Number(button.dataset.cardIndex);
        choices.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        el('status').textContent = `เลือกใบที่ ${selectedCard+1} แล้ว กดสุ่มเพื่อยืนยันรายการ`; controls();
    }));
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
        modes.forEach(button => { button.disabled = busy; });
        choices.forEach(button => { button.disabled = busy; });
        if (!el('spin')) return;
        const unavailable = !config.active || !config.rewards.length;
        el('spin').disabled = busy || (!pending && (unavailable || (mode === 'cards' && selectedCard === null)));
        el('spin').textContent = pending ? 'ตรวจสอบผลรายการเดิม' : unavailable ? 'กล่องนี้ยังไม่พร้อมสุ่ม' :
            mode === 'cards' && selectedCard === null ? 'เลือกการ์ดก่อนเริ่มสุ่ม' : config.balance < config.price ? 'เงินไม่พอ · ไปเติมเงิน' : 'สุ่ม 1 ครั้ง · ' + money(config.price);
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
    function rewardRow(reward) {
        const node = document.createElement('article'), visual = document.createElement('div');
        const title = document.createElement('h3'), chance = document.createElement('span');
        node.className = 'room-reward' + (reward.type === 'game_account' ? ' is-account' : '');
        visual.className = 'room-reward-art';
        if (reward.type === 'credit') {
            const coin = document.createElement('span'); coin.className = 'room-coin';
            coin.textContent = '฿'; coin.setAttribute('aria-hidden', 'true'); visual.append(coin);
        } else art(visual, reward);
        title.textContent = reward.title; chance.className = 'room-chance';
        chance.textContent = Number(Number(reward.chance).toFixed(4)) + '%';
        chance.setAttribute('aria-label', 'โอกาส ' + Number(reward.chance).toFixed(4) + '%');
        node.append(visual, title, chance); return node;
    }
    async function scene(data) {
        const winner = {id:data.item_id, title: data.account_title || 'เครดิต ' + money(data.credit_amount), type: data.reward_type, image: data.reward_image};
        if (mode === 'cards') {
            const button = choices[selectedCard ?? 0];
            art(button.querySelector('.gacha-choice-front'), winner);
            const label = document.createElement('small'); label.textContent = winner.title; button.querySelector('.gacha-choice-front').append(label);
            if (!skip && !reduced.matches) {
                animation = button.animate([{transform:'translateY(0) rotateY(0)'},{transform:'translateY(-12px) rotateY(90deg)'}],{duration:425,easing:'ease-in',fill:'forwards'});
                await animation.finished.catch(()=>{}); animation.cancel(); animation = null;
                button.classList.add('is-revealed');
            }
            if (!skip && !reduced.matches) {
                animation = button.animate([{transform:'translateY(-12px) rotateY(-90deg)'},{transform:'translateY(0) rotateY(0)'}],{duration:425,easing:'ease-out'});
                await animation.finished.catch(()=>{}); animation = null;
            }
            button.classList.add('is-revealed');
            if (!skip && !reduced.matches) await new Promise(resolve=>setTimeout(resolve,650));
        } else if (mode === 'wheel') {
            buildWheel(winner);
            const index = wheelRewards.findIndex(reward => reward.id === winner.id);
            const rotation = 360*5 + 360 - (index+.5)*360/wheelRewards.length;
            if (!skip && !reduced.matches) {
                animation = el('wheel').animate([{transform:'rotate(0deg)'},{transform:`rotate(${rotation}deg)`}],{duration:4200,easing:'cubic-bezier(.12,.65,.14,1)',fill:'forwards'});
                await animation.finished.catch(()=>{}); animation.cancel(); animation = null;
            }
            el('wheel').style.transform = `rotate(${rotation}deg)`;
        } else {
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
        }
        el('result').classList.toggle('gacha-gold', data.reward_type === 'game_account');
        art(el('result-art'), winner);
        el('result-text').textContent = data.result;
        el('purchase').hidden = !data.purchase_url;
        if (data.purchase_url) el('purchase').href = data.purchase_url;
        el('result').showModal(); tone(true);
    }
    async function spin() {
        if (busy) return;
        if (!pending && mode === 'cards' && selectedCard === null) return;
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
            if (Array.isArray(data.next_rewards)) {
                config.rewards = data.next_rewards;
                const list = el('rewards-list'); list.replaceChildren();
                config.rewards.forEach(reward => {
                    list.append(rewardRow(reward));
                });
                if (!config.rewards.length) {
                    const empty = document.createElement('p'); empty.className = 'col-span-full p-8 text-center'; empty.textContent = 'รางวัลหมดชั่วคราว'; list.append(empty);
                }
                const stock = el('stock');
                const accounts = config.rewards.filter(reward => reward.type === 'game_account').length;
                if (stock) stock.textContent = accounts ? `รางวัลไอดี ${accounts} รายการ` : config.rewards.length ? 'รางวัลเครดิต' : 'รางวัลหมด';
            } else if (data.reward_type === 'game_account') config.rewards = config.rewards.filter(r => r.id !== data.item_id);
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
        el('result').close();
        if (mode === 'cards') {
            selectedCard = null; choices.forEach(button => button.setAttribute('aria-pressed','false'));
            resetScene(); controls(); el('status').textContent = 'เลือกการ์ดใบใหม่ แล้วกดสุ่มอีกครั้ง'; choices[0].focus();
        } else { resetScene(); spin(); }
    });
    controls();
}
