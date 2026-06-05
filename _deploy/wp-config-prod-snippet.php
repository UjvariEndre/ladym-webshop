<?php
/**
 * ====================================================================
 *  LadyM Hungary – PROD wp-config.php kiegészítés (Rackhost)
 * ====================================================================
 *  Ezt a blokkot a Rackhost wp-config.php-jébe kell beilleszteni,
 *  a "/* That's all, stop editing! * /" sor FÖLÉ.
 *
 *  ⚠️ EZT A FÁJLT NE COMMITOLD KITÖLTVE — a kulcsok titkosak.
 *  A repóban placeholderekkel marad; a valós értékeket csak a
 *  Rackhost szerveren, FTP-vel szerkesztett wp-config.php-be írd.
 * ====================================================================
 */

/* --------------------------------------------------------------------
 * 1) Cloudflare R2 média-offload (ladym-core/r2-offload.php)
 *
 *  ⚠️ ROTÁLÁSI LÁNC — KÖTELEZŐ SORREND:
 *  Az S3 kulcspár a Cloudflare `cfat_` tokenből van LEVEZETVE:
 *      Access Key ID = token ID,  Secret = SHA-256(token value).
 *  A régi token + kulcsok a fejlesztői chatbe kerültek → go-live ELŐTT
 *  ROTÁLD a Cloudflare-en, és az ÚJ derivált kulcsokat tedd ide.
 *  Ha a régi (kompromittált) kulcsokat írod be, az offload működik
 *  ugyan, de a kulcs továbbra is kiszivárgott marad.
 * ------------------------------------------------------------------ */
define( 'LADYM_R2_KEY',        '__UJ_R2_ACCESS_KEY_ID__' );      // rotált token ID
define( 'LADYM_R2_SECRET',     '__UJ_R2_SECRET_ACCESS_KEY__' );  // SHA-256(rotált token value)
define( 'LADYM_R2_BUCKET',     'ladym-media' );
define( 'LADYM_R2_ENDPOINT',   'https://bef88485e02274c92fb1250ca7931216.eu.r2.cloudflarestorage.com' );
define( 'LADYM_R2_PUBLIC_URL', 'https://media.ladymhungary.com' );
define( 'LADYM_R2_REGION',     'auto' );

/* --------------------------------------------------------------------
 * 2) Biztonság / éles üzem ajánlott kapcsolók
 * ------------------------------------------------------------------ */
define( 'DISALLOW_FILE_EDIT', true );   // beépített fájlszerkesztő tiltása az adminban
define( 'WP_DEBUG',           false );  // éles: ne logoljon a frontendre
define( 'WP_DEBUG_DISPLAY',   false );
@ini_set( 'display_errors', 0 );
define( 'WP_AUTO_UPDATE_CORE', 'minor' ); // csak biztonsági/minor auto-update
// ⚠️ Blocksy auto-update buktató: a téma egyszer kiürült auto-update miatt
//    (lásd memória: wpenv-theme-wiped-autoupdate). Élesben tartsd szemmel,
//    vagy tiltsd a téma-auto-update-et az adminban.

/* --------------------------------------------------------------------
 * 3) Stripe LIVE kulcsok – NEM ide, hanem a WooCommerce beállításba/DB-be
 *    kerülnek (a migrált woocommerce_stripe_settings opcióban). Élesítéskor:
 *      testmode=no, pk_live_/sk_live_, webhook_secret (whsec_).
 *    Lásd a DEPLOY-RUNBOOK.md 7. lépését.
 * ------------------------------------------------------------------ */
