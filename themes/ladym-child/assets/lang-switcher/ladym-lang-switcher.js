/**
 * LadyM nyelvválasztó – fejléc-dropdown viselkedés + injektálás.
 *
 * A switcher markupját a PHP egy rejtett <template id="ladym-langsw-tpl">-be teszi a
 * láblécbe; ez a szkript a fejléc (#header) search (desktop) ill. trigger (mobil) eleme
 * elé szúrja be — így a Blocksy header-elemregiszter módosítása nélkül kerül a search ikon
 * mellé balra. A dropdown kattintásra nyílik, kattints-mellé / Esc zár.
 */
(function () {
  'use strict';

  function getNode(tpl) {
    if (tpl.content && tpl.content.firstElementChild) {
      return tpl.content.firstElementChild.cloneNode(true);
    }
    var d = document.createElement('div');
    d.innerHTML = tpl.innerHTML.trim();
    return d.firstElementChild;
  }

  function inject() {
    var tpl = document.getElementById('ladym-langsw-tpl');
    var header = document.getElementById('header');
    if (!tpl || !header) return;
    // Desktop: a search elé; mobil: a hamburger (trigger) elé. Soronként egy.
    var anchors = header.querySelectorAll('[data-id="search"], [data-id="trigger"]');
    Array.prototype.forEach.call(anchors, function (anchor) {
      var parent = anchor.parentNode;
      if (!parent || parent.querySelector('.ladym-langsw')) return;
      var node = getNode(tpl);
      if (node) parent.insertBefore(node, anchor);
    });
  }

  function closeAll(except) {
    var open = document.querySelectorAll('.ladym-langsw.is-open');
    Array.prototype.forEach.call(open, function (s) {
      if (s === except) return;
      s.classList.remove('is-open');
      var b = s.querySelector('.ladym-langsw__btn');
      if (b) b.setAttribute('aria-expanded', 'false');
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('.ladym-langsw__btn') : null;
    if (btn) {
      e.preventDefault();
      var sw = btn.closest('.ladym-langsw');
      var open = sw.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      closeAll(sw);
      return;
    }
    if (!(e.target.closest && e.target.closest('.ladym-langsw'))) closeAll(null);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAll(null);
  });

  if (document.readyState !== 'loading') inject();
  else document.addEventListener('DOMContentLoaded', inject);
})();
