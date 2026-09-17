const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'C:/Users/ACER/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

const root = path.resolve(__dirname, '..');
const output = path.join(root, 'storage/app/private/review-browser');
const mime = {'.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png', '.svg': 'image/svg+xml', '.webp': 'image/webp'};
let spins = 0;
const server = http.createServer((req, res) => {
    const pathname = new URL(req.url, 'http://127.0.0.1:8187').pathname;
    if (pathname === '/gacha/1/spin') {
        spins++;
        res.setHeader('Content-Type', 'application/json');
        return res.end(JSON.stringify({success: true, item_id: 1, reward_type: 'credit', credit_amount: 25,
            new_balance: 1000, result: 'เครดิต ฿25.00', spin_id: spins}));
    }
    const fixture = pathname === '/wallet' ? 'wallet' : pathname === '/gacha/1' ? 'gacha' : null;
    if (fixture) {
        const data = JSON.parse(fs.readFileSync(path.join(output, `${fixture}.json`)));
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.setHeader('Content-Security-Policy', data.csp);
        return res.end(data.html);
    }
    if (pathname === '/notifications/count') {
        res.setHeader('Content-Type', 'application/json');
        return res.end('{"count":0}');
    }
    const publicRoot = path.join(root, 'public');
    const target = path.resolve(publicRoot, '.' + pathname);
    if (target.startsWith(publicRoot + path.sep) && fs.existsSync(target) && fs.statSync(target).isFile()) {
        res.setHeader('Content-Type', mime[path.extname(target)] || 'application/octet-stream');
        return fs.createReadStream(target).pipe(res);
    }
    res.writeHead(404); res.end();
});

(async () => {
    await new Promise(resolve => server.listen(8187, '127.0.0.1', resolve));
    const browser = await chromium.launch({headless: true,
        executablePath: process.env.BROWSER_EXECUTABLE || 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'});
    try {
        for (const width of [390, 1440]) {
            const page = await browser.newPage({viewport: {width, height: 950}, reducedMotion: 'reduce'});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            page.on('console', message => {
                if (message.type() === 'error' && /content.security.policy|violat|refused/i.test(message.text())) errors.push(message.text());
            });
            await page.addInitScript(() => {
                window.cspViolations = [];
                document.addEventListener('securitypolicyviolation', event => window.cspViolations.push(event.violatedDirective + ': ' + event.blockedURI));
            });
            await page.goto('http://127.0.0.1:8187/wallet', {waitUntil: 'networkidle'});
            await page.selectOption('#payment-method', 'truemoney_gift');
            assert(await page.locator('#truemoney-qr').isVisible());
            assert(!await page.locator('#promptpay-qr').isVisible());
            await page.selectOption('#payment-method', 'promptpay_slip');
            assert(await page.locator('#promptpay-qr').isVisible());
            assert.deepEqual(await page.evaluate(() => window.cspViolations), []);
            await page.goto('http://127.0.0.1:8187/gacha/1', {waitUntil: 'networkidle'});
            for (const mode of ['cards', 'wheel', 'box', 'cards']) {
                await page.locator(`[data-gacha-mode="${mode}"]`).click();
                assert.equal(await page.locator('#gacha-box-note').isVisible(), mode === 'box');
                assert.equal(await page.locator('#gacha-cards-scene').isVisible(), mode === 'cards');
                assert.equal(await page.locator('#gacha-wheel-scene').isVisible(), mode === 'wheel');
                if (mode === 'cards') {
                    await page.locator('[data-card-index="2"]').click();
                    assert(await page.getByText('การเลือกใบไม่เปลี่ยนโอกาสได้รับรางวัล ผลรางวัลกำหนดโดยเซิร์ฟเวอร์', {exact: true}).isVisible());
                }
                await page.locator('#gacha-stage').screenshot({path: path.join(output, `${process.env.REVIEW_MODE || 'built'}-${mode}-${width}.png`)});
                const before = spins;
                await page.locator('#gacha-spin').click();
                await page.locator('#gacha-result').waitFor({state: 'visible'});
                assert.equal(spins, before + 1);
                await page.locator('#gacha-result').evaluate(dialog => dialog.close());
            }
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
            assert.deepEqual(await page.evaluate(() => window.cspViolations), []);
            assert.deepEqual(errors, []);
            console.log(JSON.stringify({mode: process.env.REVIEW_MODE || 'built', width, wallet: 'passed', gachaModes: 'passed', cspViolations: 0, pageErrors: 0}));
            await page.close();
        }
    } finally {
        await browser.close();
        server.close();
    }
})().catch(error => { console.error(error); server.close(); process.exitCode = 1; });
