# LadyM Hungary – Élesítési runbook (Rackhost WP Midi)

> Cél: a lokális `wp-env` bolt → **Rackhost** éles tárhely, **FTP/SFTP** hozzáféréssel.
> Migrációs eszköz: **All-in-One WP Migration** (free) — a DB-importot PHP-ből végzi,
> így nem kell phpMyAdmin/SSH. Az oldal pici (~33 MB: 6 MB DB + 27,5 MB uploads),
> a média R2-n van → a free verzió bőven elég.
>
> Prod domain: **https://ladymhungary.com** (erősítsd meg: www vs. non-www!).
> A `__PLACEHOLDER__` értékeket élesben töltöd ki, NEM a repóban.

---

## 0) Előfeltételek (mielőtt kezded)
- [ ] Rackhost WP Midi előfizetés aktív, friss WordPress feltelepítve (a Midi ezt adja).
- [ ] Rackhost **wp-admin** belépés megvan (a friss WP admin user).
- [ ] **FTP/SFTP** hozzáférés (host, user, jelszó/kulcs) megvan.
- [ ] A `ladymhungary.com` domain a Rackhost felé mutat (A-rekord / Cloudflare).
- [ ] **SSL** kiadva (Rackhost Let's Encrypt vagy Cloudflare).

---

## 1) Export a LOKÁLIS boltból (böngésző, ~30 mp)
1. Indítsd az envet, ha nem fut: `npx wp-env start`
2. Lépj be: http://localhost:8888/wp-admin (admin / password)
3. Bal menü: **All-in-One WP Migration → Export**
4. **Export To → File** → vár, amíg legenerálja → **Download**
5. Megvan a `ladym-hungary-*.wpress` fájl (~33 MB). Ezt visszük a Rackhostra.

> A `.wpress` MAGÁBAN HORDOZZA a `localhost:8888` → prod-domain cserét? NEM automatikusan
> a fájlban — az AIO az **import** során, a céloldal URL-jéhez igazítva cseréli le a
> szerializált adatokban is. Ezért fontos, hogy a Rackhost WP már a végleges domainen fusson.

---

## 2) Import a RACKHOSTRA (FTP + böngésző)
1. Rackhost wp-admin → **Bővítmények → Új → "All-in-One WP Migration"** telepítés + aktiválás
   (ugyanaz a plugin kell a célon is).
2. **FTP-vel** töltsd fel a `.wpress` fájlt ide:
   `wp-content/ai1wm-backups/`
   *(Így megkerülöd a böngészős feltöltési méretlimitet — ez az FTP-only workflow lényege.)*
3. wp-admin → **All-in-One WP Migration → Backups** → a feltöltött fájl megjelenik → **Restore**.
4. Az import felülírja a cél-DB-t és fájlokat, és lecseréli az URL-eket a Rackhost domainre.
5. A végén az AIO kéri: **Permalinks → kétszer Mentés** (frissíti a `.htaccess`-t).
6. Belépés újra a MIGRÁLT admin userrel (a lokál `admin`/`dev` jelszóval — a lokál DB jött át!).

> ⚠️ A migráció után a Rackhost eredeti admin usere helyett a **lokál** userek élnek
> (`dev` Administrator, Tünde shop_manager, a lezárt `admin`). A jelszavak a lokáliak.

---

## 3) wp-config.php – konstansok (FTP)
A migráció a **DB-t** hozza át, a `wp-config.php` konstansait NEM. FTP-vel szerkeszd a
Rackhost `wp-config.php`-ját, és illeszd be a `_deploy/wp-config-prod-snippet.php` blokkot
(a `/* That's all, stop editing! */` sor **fölé**).

- [ ] **R2 offload** 6 konstans (lásd lent, 4. lépés a rotálásról!)
- [ ] `DISALLOW_FILE_EDIT`, `WP_DEBUG=false`, auto-update kapcsolók.

---

## 4) ⚠️ Token-rotálás (KÖTELEZŐ, go-live előtt)
A régi Cloudflare `cfat_` token és a belőle derivált S3-kulcsok a fejlesztői chatbe kerültek.

**Sorrend (a derivációs lánc miatt KÖTELEZŐ):**
1. Cloudflare dashboard → **R2 → Manage R2 API Tokens** → a régi token **Roll/Delete**, új token létrehozása
   (Object Read & Write, `ladym-media` bucket, EU).
2. Az ÚJ tokenből vezesd le az S3-kulcsot:
   `Access Key ID = token ID`, `Secret = SHA-256(token value)`.
3. Az **ÚJ** `LADYM_R2_KEY` / `LADYM_R2_SECRET` értékeket írd a Rackhost `wp-config.php`-ba (3. lépés).
4. Ellenőrzés: tölts fel egy tesztképet az adminból → menjen ki R2-re, jelenjen meg
   `media.ladymhungary.com`-ról. (A meglévő 26 kép URL-je már R2-re mutat a DB-ben.)
5. Frissítsd a lokális `.r2-credentials` fájlt az új értékekkel (gitignore-olt).

---

## 5) SEO kapcsolók – noindex LE (Search Console)
Jelenlegi állapot: `blog_public=0` (noindex). **Élesben:**
- [ ] Rackhost wp-admin → **Beállítások → Olvasás** → vedd ki a „Keresőmotorok láthatósága" pipát
      (vagy WP-CLI, ha lesz: `wp option update blog_public 1`).
- [ ] **Rank Math** → ellenőrizd, hogy a robots már `index, follow`.
- [ ] **Google Search Console**: property hozzáadása (domain v. URL-prefix), tulajdon igazolás,
      `https://ladymhungary.com/sitemap_index.xml` beküldése.

## 6) WooCommerce „Coming Soon" LE
Jelenlegi: `woocommerce_coming_soon=yes`, `woocommerce_store_pages_only=yes` (bolt elrejtve).
- [ ] wp-admin → **WooCommerce → Beállítások → Webáruház láthatósága** (vagy a felső
      „Coming soon" jelölő) → **Élő/Live** (mindkét opció `no`).

---

## 7) Stripe – LIVE üzemmód
Jelenlegi: `testmode=yes`, teszt kulcsok. **Élesben (wp-admin → WooCommerce → Payments → Stripe):**
- [ ] `pk_live_...` / `sk_live_...` (a Stripe Dashboard **Live** kulcsai).
- [ ] **Test mode → OFF**.
- [ ] **Webhook:** Stripe Dashboard → Developers → Webhooks → endpoint hozzáadása
      a plugin által megadott URL-re (`https://ladymhungary.com/?wc-ajax=wc_stripe_*`),
      a `whsec_...` titok a Stripe gateway **Webhook secret** mezőjébe.
- [ ] Stripe Dashboard → Settings → Payments → **Link OFF** (a kártya-elemen belüli Link-autofill
      csak itt kapcsolható ki — lásd memória `payment-stripe-setup`).
- [ ] Marad: **Bankkártya (Stripe)** + **Banki átutalás (BACS)**. COD ki, Link/Express ki.

---

## 8) Email – éles SMTP (KRITIKUS, különben spam/nem kézbesül)
Jelenlegi: `wp-mail-smtp` mailer = `mail` (PHP mail, lokálisan nem megy ki).
- [ ] wp-admin → **WP Mail SMTP → Settings** → válassz SMTP-szolgáltatót
      (Rackhost SMTP, vagy pl. egy tranzakciós szolgáltató).
- [ ] Feladó: **„Lady M Hungary" <info@ladymhungary.com>** (már beállítva a WC-ben).
- [ ] **SPF + DKIM** beállítása a domainen (különben a Gmail/Outlook spamre teszi).
- [ ] **Tesztlevél** kiküldése (WP Mail SMTP → Email Test).
- [ ] Élő teszt: adj le egy rendelést → menjen ki a vevő-visszaigazoló + a boltos-értesítő +
      az **elállási** modul visszaigazolója (lásd memória `withdrawal-feature`).

---

## 9) Teljes vásárlási teszt (ÉLESBEN, valódi kártyával kis összeg)
- [ ] Termék → Kosár → Pénztár → **Bankkártya (Stripe LIVE)** → fizetés.
- [ ] Rendelés `processing`-re vált, a Stripe charge `succeeded`.
- [ ] Emailek megérkeznek (8. lépés).
- [ ] Próbáld az **átutalást (BACS)** is (megjelenik-e a számlaszám).
- [ ] Elállási űrlap (`/elallas/`) → CPT + email létrejön.
- [ ] (Utána a teszt-rendelést vissza lehet téríteni / törölni.)

---

## 10) Cloudflare cache-szabályok (ha CF van a domain előtt)
A dinamikus oldalak NE cache-elődjenek:
- [ ] **Bypass cache**: `/kosar/`, `/penztar/`, `/fiokom/`, `/elallas/`, `/wp-admin/`, `/?wc-ajax=*`,
      és minden `wp-*` cookie-val rendelkező kérés.
- [ ] Statikus assetek (CSS/JS/kép) cache-elhetők.
- [ ] LiteSpeed/oldalcache plugin: a fenti oldalakat zárd ki (lásd memória `local-frontend-debug-gotchas`).

---

## 11) Jogi / tartalmi utolsó kör (Tündi/jogász)
- [ ] **Elállási** modul jogi szövegeinek jóváhagyása (`withdrawal-feature` — határidő **2026-06-19**).
- [ ] Footer copyright placeholder cseréje brand-szövegre, ha még „WordPress Theme by…" lenne.
- [ ] ÁSZF / Adatvédelmi tájékoztató / Impresszum véglegesítve és linkelve a láblécben.
- [ ] **Számlázás (NAV):** Tündi dönt (Számlázz.hu / Billingo) — launch-kritikus, külön lépés.

---

## 12) Élesítés után – ellenőrzések
- [ ] `https://ladymhungary.com` betölt, HERO + logó + menü + footer OK.
- [ ] Termékkép R2-ről jön (`media.ladymhungary.com`), nincs törött kép.
- [ ] `view-source` → `robots` = index,follow; OG-image kép.
- [ ] `sitemap_index.xml` 200, beküldve GSC-be.
- [ ] Mobil nézet (a forgalom IG/TikTokról, mobilról jön).
- [ ] Stripe LIVE rendelés sikeres, emailek mennek.

---

### Gyors státusz-referencia (lokál, 2026-06-05)
| Kapcsoló | Lokál most | Élesben kell |
|---|---|---|
| `blog_public` | `0` (noindex) | **`1`** |
| `woocommerce_coming_soon` | `yes` | **`no`** |
| `woocommerce_store_pages_only` | `yes` | **`no`** |
| Stripe `testmode` | `yes` | **`no`** + live kulcsok |
| `wp-mail-smtp` mailer | `mail` | **valódi SMTP** + SPF/DKIM |
| R2 konstansok | wp-env wp-config | **Rackhost wp-config** (rotált kulcs!) |
