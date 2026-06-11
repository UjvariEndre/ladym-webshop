// Egyszeri generátor: brand-stílusú placeholder SVG-k a "Szabd magadra" konfigurátorhoz.
// A végleges (AI / fotó) képek később ugyanezekkel a fájlnevekkel cserélhetők (jpg/webp is mehet,
// a PHP a kiterjesztés-listát végigpróbálja). Futtatás: node _tools/gen-placeholders.mjs
import { mkdirSync, writeFileSync } from 'node:fs';

const OUT = new URL('../themes/ladym-child/assets/szabd-magadra/', import.meta.url);
mkdirSync(OUT, { recursive: true });

// 3 textil hangulatszín (a brand palettából): szilva, slate-lila, mályva.
const TEXTILES = [
  { idx: 1, name: 'Anyag 1', tone: '#65273c', bg: '#f1e4e9', ink: '#4b1c2c' },
  { idx: 2, name: 'Anyag 2', tone: '#5b5b78', bg: '#e7e7ef', ink: '#3a3a52' },
  { idx: 3, name: 'Anyag 3', tone: '#b08fa0', bg: '#f1ecef', ink: '#6b4f5c' },
];

const FAZONS = [
  { slug: 'melleny', name: 'Mellény' },
  { slug: 'kabat',   name: 'Kabát' },
  { slug: 'szett',   name: 'Szett' },
];

// Finom folk-textúra: ritka rombuszrács a textil tónusában.
function texture(id, tone) {
  return `
  <pattern id="${id}" width="34" height="34" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
    <path d="M17 0 L34 17 L17 34 L0 17 Z" fill="none" stroke="${tone}" stroke-width="1" opacity="0.18"/>
  </pattern>`;
}

// Egyszerű, fazononként eltérő ruha-sziluett (a 300,150 origótól).
function garment(slug, fill, stroke) {
  const common = `fill="${fill}" stroke="${stroke}" stroke-width="3" stroke-linejoin="round"`;
  if (slug === 'melleny') {
    // Ujjatlan, V-nyakú mellény, derékig.
    return `<path ${common} d="M230 200 L300 235 L370 200 L360 250 L375 470 L225 470 L240 250 Z"/>
      <path d="M300 235 L300 360" fill="none" stroke="${stroke}" stroke-width="2" opacity="0.5"/>`;
  }
  if (slug === 'kabat') {
    // Hosszú kabát ujjakkal, térdig.
    return `<path ${common} d="M232 196 L300 226 L368 196 L398 250 L372 300 L380 560 L220 560 L228 300 L202 250 Z"/>
      <path d="M300 226 L300 540" fill="none" stroke="${stroke}" stroke-width="2" opacity="0.5"/>`;
  }
  // Szett: felső + szoknya, kétrészes.
  return `<path ${common} d="M236 198 L300 226 L364 198 L388 246 L368 286 L356 360 L244 360 L232 286 L212 246 Z"/>
    <path ${common} d="M250 380 L350 380 L378 540 L222 540 Z"/>`;
}

function previewSvg(t, f) {
  const texId = `tex${t.idx}`;
  return `<svg xmlns="http://www.w3.org/2000/svg" width="600" height="800" viewBox="0 0 600 800" role="img" aria-label="${t.name} – ${f.name} előnézet">
  <defs>${texture(texId, t.tone)}</defs>
  <rect width="600" height="800" fill="${t.bg}"/>
  <rect width="600" height="800" fill="url(#${texId})"/>
  <g>${garment(f.slug, t.tone, t.ink)}</g>
  <rect x="40" y="688" width="520" height="72" fill="#ffffff" opacity="0.86"/>
  <text x="300" y="722" text-anchor="middle" font-family="Georgia, 'Playfair Display', serif" font-size="30" fill="${t.ink}">${f.name}</text>
  <text x="300" y="748" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="15" letter-spacing="3" fill="${t.tone}">${t.name.toUpperCase()}</text>
</svg>`;
}

function swatchSvg(t) {
  const texId = `stex${t.idx}`;
  return `<svg xmlns="http://www.w3.org/2000/svg" width="480" height="480" viewBox="0 0 480 480" role="img" aria-label="${t.name} textil">
  <defs>${texture(texId, '#ffffff')}</defs>
  <rect width="480" height="480" fill="${t.tone}"/>
  <rect width="480" height="480" fill="url(#${texId})"/>
  <rect x="34" y="400" width="412" height="48" fill="#ffffff" opacity="0.9"/>
  <text x="240" y="432" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="20" letter-spacing="3" fill="${t.ink}">${t.name.toUpperCase()}</text>
</svg>`;
}

let n = 0;
for (const t of TEXTILES) {
  writeFileSync(new URL(`swatch-textil${t.idx}.svg`, OUT), swatchSvg(t)); n++;
  for (const f of FAZONS) {
    writeFileSync(new URL(`textil${t.idx}-${f.slug}.svg`, OUT), previewSvg(t, f)); n++;
  }
}
console.log(`Generated ${n} SVG placeholders in ${OUT.pathname}`);
