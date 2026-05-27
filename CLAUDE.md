# LadyM Hungary – WooCommerce webshop (projekt-kontextus)

Ez a fájl a Claude Code-nak ad kontextust. Cél: kevesebb magyarázkodás, gyorsabb, konzisztens munka.

## Mi ez a projekt

LadyM Hungary fashion webshop — kézzel készített, **upcycled / vintage** divat (kabátok, mellények, kiegészítők). Magyar műhely (Nyúl, HU). Kis katalógus (induláskor ~30 termék), HU + EN nyelv, IG/TikTok funnel a fő forgalmi forrás. Arculat-referencia: a meglévő landing page (webellus.com/ladym).

## Stack

- **Lokális env:** `@wordpress/env` (Docker, WSL2). Indítás: `npx wp-env start`. WP: http://localhost:8888 (admin / password).
- **WP-CLI** a konténerben: `npx wp-env run cli wp <parancs>`
- **CMS:** WordPress + **WooCommerce**
- **Téma:** Blocksy (szülő) + `ladym-child` (egyedi child theme) — minden egyedi CSS/PHP ide
- **Egyedi plugin:** `ladym-core` (témafüggetlen, site-specifikus funkciók, WooCommerce hookok)
- **Kétnyelvűség:** Polylang (HU alap, EN másodlagos) — *Fázis 2-ben jön*
- **Éles tárhely (terv):** Rackhost WP Midi + Cloudflare

## Repo-struktúra (verziózott rész)

```
ladym-webshop/
├── .wp-env.json            # WordPress core, WooCommerce + Blocksy (zip), local mappingek
├── package.json            # @wordpress/env devDependency, scriptek
├── themes/ladym-child/     # child theme: style.css (brand), functions.php
└── plugins/ladym-core/     # egyedi plugin
```

A WordPress core és az uploads a `~/.wp-env/` alatt élnek, **NEM** a repóban. Csak a fenti saját kódot verziózzuk.

## Brand tokenek (a landing page-ből, Tailwind v4 -> CSS)

```
--color-ivory:        #fbfaf6   /* háttér */
--color-paper:        #ffffff
--color-cream-warm:   #f4eee3
--color-ink:          #131830   /* fő szöveg, navy-ink */
--color-ink-soft:     #4a5673
--color-burgundy:     #8e1f2e   /* fő akcent (gombok, ár, linkek) */
--color-burgundy-deep:#6b1422   /* hover */
--color-blush:        #e8d4c8
--color-mocha:        #5a6585
--color-line:         #e5ded1   /* keretek, elválasztók */
```

**Tipográfia:** Playfair Display (címsorok, serif, 400–700) + Inter (törzs, sans). Mindkettő latin + latin-ext (magyar ékezetek).

**Stílusjegyek:** sok levegő, editorial gombok (lekerekítés nélkül, nagybetűs, bordó), finom fade-up/reveal animációk (`cubic-bezier(0.16,1,0.3,1)`), „underline-grow" hover linkekre, bordó kijelölés-szín, folk-pattern textúra a hero mögött.

## Konvenciók

- Egyedi PHP a `ladym-core` pluginba (témafüggetlen logika) vagy a child theme `functions.php`-jébe (megjelenéshez kötött). WooCommerce template override: `themes/ladym-child/woocommerce/...`.
- Minden szöveg legyen fordításra kész (`__()`, `_e()`, text domain: `ladym` / `ladym-core`), mert jön a Polylang.
- HUF ár, magyar formátum (pl. `12 990 Ft`, tizedes nélkül).
- Sebesség = bevétel: ne pakoljunk felesleges plugint; képek WebP/AVIF, lazy-load.
- Reszponzív-first: a forgalom nagy része mobilon, IG/TikTok-ról jön.
- Git: kis, fókuszált commitok, fázisonként.

## Hasznos parancsok

```bash
npx wp-env start                                   # env indítása
npx wp-env run cli wp plugin list                  # pluginok
npx wp-env run cli wp theme list                   # témák
npx wp-env run cli wp option get woocommerce_currency
npx wp-env run cli wp post list --post_type=page --fields=ID,post_title,post_status
npx wp-env stop
```

## Hol tartunk

Lásd a `ROADMAP.md`-t. Jelenleg: **Fázis 1 (téma + alapkonfiguráció)**, a végén a menü/logó/Blocksy-paletta összehangolás, majd **Fázis 2 (Polylang, HU/EN)**.