# LadyM Hungary – Webshop ROADMAP

## Fázis 1 – Téma + alapkonfiguráció ✅ (kész)

- WooCommerce magyar bolt: HUF, HU, `12 990 Ft` formátum (right_space, szóköz ezres, vessző tizedes, 0 tizedesjegy), kg/cm.
- Főmenü a Blocksy elsődleges helyén (`menu_1`): Főoldal, Shop, Rólunk, Kapcsolat.
- Szöveges logó: „Lady M Hungary" (site title).
- Blocksy globális paletta a brand tokenekhez (akcent #8e1f2e, szöveg #131830, háttér #fbfaf6).

> A Fázis 1 konfigurációja nagyrészt DB-szintű (wp-env, nem verziózott).

## Fázis 2 – Kétnyelvűség (HU/EN) ⏸️ (szüneteltetve – jelenleg CSAK HU)

> **Döntés (2026-05-27):** az EN **egyelőre elrejtve, csak magyar piac**. A kétnyelvűség struktúrája megvan,
> később visszakapcsolható. Ok: a Shop-nak nincs EN párja (termékek HU-only), ezért a nyelvváltó a shopon a HU-ra ugrott.
> **Mi történt (reverzibilis):** az EN nyelv törölve a Polylangból (nincs nyelvváltó, nincs `/en/`, nincs hreflang),
> az EN oldalak (*About us* #17, *Contact* #18) a **kukába** helyezve (trash, nem végleges – visszaállítható),
> az EN menü törölve, a nyelvváltó eltávolítva a Főmenüből. A Polylang **telepítve és aktív marad**.
> **Visszakapcsolás:** lásd a lenti „Kész" lépéseket – az EN nyelv újra felvétele + EN menü + váltó újraépítése
> (a child theme `.lang-item` CSS-e bent maradt, újra él, amint van switcher). Előbb a Shop EN-stratégiát kell rendezni
> (Fázis 2.5), különben ugyanaz a shop-bug visszajön.

**Korábban kész volt (visszakapcsoláskor irányadó):**
- **Polylang** telepítve reprodukálhatóan a `.wp-env.json` `plugins` tömbjéből (`polylang.zip`).
- Nyelvek: **HU alapértelmezett** (prefix nélkül), **EN másodlagos** (`/en/` prefix).
  URL-séma: `force_lang=1` (könyvtár-prefix) + `hide_default=true`.
- Minden meglévő oldal/bejegyzés a HU nyelvhez rendelve (WC oldalak: Shop/Cart/Checkout/My account is).
- EN oldalpárok: Rólunk → *About us* (`/en/about-us/`), Kapcsolat → *Contact* (`/en/contact/`), „Coming soon." placeholderrel, nyelvi párként összekötve.
- **Nyelvváltó** a fejléc menüben (Polylang menüelem), zászló nélkül, nyelvkóddal (HU/EN – `display_names_as=slug` + CSS uppercase a child theme-ben).
- **Nyelvenként KÜLÖN menü** a `menu_1` helyen (a Polylang a megosztott menü pontjait NEM fordítja le – nyelvenként külön menü kell):
  - HU: „Főmenü" (#16) – Főoldal, Shop, Rólunk, Kapcsolat, váltó → HU URL-ek.
  - EN: „Main Menu (EN)" (#26) – Home (`/en/`), Shop, About us (`/en/about-us/`), Contact (`/en/contact/`), váltó → EN URL-ek.
  - Így a navigáció a `/en/`-en végig EN marad, és a váltó a megfelelő nyelvi párra visz.
- hreflang: a Polylang automatikusan kezeli.

**Hátralévő / kézi finomítás (admin):**
- Az UI-stringek (Blocksy/WooCommerce) fordításainak átnézése a *Nyelvek → Stringfordítások* alatt.
- **Shop link az EN menüben** egyelőre a HU `/shop/`-ra mutat (a termékek HU-only – lásd Fázis 2.5). Ha lesz EN termékkatalógus, az EN „Shop" a `/en/shop/`-ra állítandó.
- Általános WP/WC `.mo` fordítócsomagok frissítése (`wp language core/plugin install en_US`, ha kell).

### ⏳ Termékfordítás – KÉSŐBBRE HALASZTVA (fontos korlát)

A **Polylang ingyenes** verziója a WooCommerce **termékek** (és termék-kategóriák/-attribútumok) mély,
kétnyelvű kezelését **nem** oldja meg teljeskörűen – ehhez a fizetős **Polylang for WooCommerce**
kiegészítő kell. Mivel **most nem telepítünk fizetőset**, a termékkatalógus egyelőre **egynyelvű (HU)**.

**Stratégia, amikor sorra kerül (Fázis 2.5 / 3):**
1. **Opció A – Polylang for WooCommerce (fizetős):** a leg­tisztább. Termékenként HU/EN változat,
   kategória/attribútum-fordítás, nyelvenkénti WC-oldalak (Shop/Cart/Checkout/My account) automatikusan.
2. **Opció B – kétnyelvű terméktartalom egy nyelven belül:** rövid EN leírás a HU termékleíráshoz
   (induló ~30 termékes katalógusnál járható, nulla plugin­költség, de nincs külön `/en/` termék-URL).
3. **Opció C – alternatív i18n plugin** (pl. TranslatePress/WPML) értékelése – csak ha az A/B nem elég.

Döntési input: katalógusméret növekedése + EN forgalom aránya (IG/TikTok). A katalógus kicsi marad →
egyelőre **B** is elég lehet; ha nő az EN konverzió → **A**.

## Fázis 3 – Tartalom & arculat (főoldal) 🟡 (folyamatban)

**1. lépés – HERO + logó ✅ (kész, csak HU):**
- **Kép-logó:** `assets/ladym-logo.svg` a médiatárba importálva (att. #27), Blocksy `custom_logo` theme_mod-ra állítva,
  ~40px magasság a child theme CSS-éből (a fragilis header-builder `logoMaxHeight` theme_mod helyett). SVG-feltöltés
  engedélyezve `functions.php`-ben (admin-only `upload_mimes` + `wp_check_filetype_and_ext` szűrő).
- **HERO:** child theme `front-page.php` (verziózott, egy forrás; brand tokenek CSS-ben, nincs hardcode hex).
  Bal kártya (eyebrow / „Lady M" + dőlt bordó „Hungary" / tagline / intro / 2 CTA → Shop), jobb 4/5 kép-placeholder
  sötét feliratsávval (EGYEDI DARAB / *One-of-a-kind*). `.hero-pattern` folk-textúra háttér. Mobil-first
  (mobilon kép felül, nagy képernyőn 7/5 bal szöveg/jobb kép), `.ladym-fade-up` animáció.
- **Statikus kezdőlap:** „Főoldal" oldal (#28) létrehozva, `show_on_front=page`, `page_on_front=28`.

**Hátralévő lépések (terv):** termékkiemelő/„Új érkezések" szekció, About/atelier sztori-blokk, Instagram-feed,
hírlevél/CTA-sáv, lábléc-tartalom. A 4/5 placeholder cseréje valódi termékfotóra (Fázis 4 / fotózás).

## Fázis 4 – Éles üzem (terv)

- Rackhost WP Midi + Cloudflare, képoptimalizálás (WebP/AVIF), gyorsítótár, SEO/hreflang ellenőrzés élesben.
