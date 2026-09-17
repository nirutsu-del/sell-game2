const fs = require('node:fs');
const path = require('node:path');
const sharp = require(process.env.CODEX_SHARP_PATH || 'sharp');

async function main() {
    const root = path.resolve(__dirname, '..');
    const manifest = JSON.parse(fs.readFileSync(path.join(root, 'docs/generated-account-artwork.json'), 'utf8'));
    const destination = path.join(root, 'storage/app/public/demo-accounts/batch-new');
    fs.mkdirSync(destination, { recursive: true });
    for (const item of manifest.completed) {
        const filename = `${item.key}-${item.theme.toLowerCase()}.webp`;
        await sharp(item.path).resize({ width: 1200, withoutEnlargement: true }).webp({ quality: 86 }).toFile(path.join(destination, filename));
    }
    console.log(`Prepared ${manifest.completed.length} images.`);
}
main().catch(error => { console.error(error); process.exitCode = 1; });
