// Genera los PNG de public/icons/ a partir de public/icons/icon.svg
// Ejecutar: node scripts/generate-icons.mjs (requiere @playwright/test instalado)
import { chromium } from '@playwright/test';
import { readFileSync, copyFileSync } from 'fs';

const sizes = [72, 96, 128, 144, 152, 192, 384, 512];
const svg = readFileSync('public/icons/icon.svg', 'utf8');

const browser = await chromium.launch();
const page = await browser.newPage();

for (const s of sizes) {
    await page.setViewportSize({ width: s, height: s });
    await page.setContent(
        `<style>*{margin:0;padding:0}svg{display:block;width:${s}px;height:${s}px}</style>${svg}`,
    );
    await page.screenshot({ path: `public/icons/icon-${s}x${s}.png`, omitBackground: true });
    console.log(`Generado: icon-${s}x${s}.png`);
}

// Favicon (PNG de 48px; los navegadores modernos lo aceptan como .ico)
await page.setViewportSize({ width: 48, height: 48 });
await page.setContent(
    `<style>*{margin:0;padding:0}svg{display:block;width:48px;height:48px}</style>${svg}`,
);
await page.screenshot({ path: 'public/icons/icon-48x48.png', omitBackground: true });
copyFileSync('public/icons/icon-48x48.png', 'public/favicon.ico');
console.log('Generado: favicon.ico');

await browser.close();
