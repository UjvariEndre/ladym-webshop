/**
 * LadyM Lightbox – újrahasználható, függőség nélküli kép-nagyító / galéria komponens.
 *
 * Használat (markup-konvenció): bármely elemre tedd rá a `data-ladym-lb` attribútumot.
 *   <img class="ladym-zoomable" src="thumb.jpg" alt="…"
 *        data-ladym-lb
 *        data-ladym-lb-full="full.jpg"     // opcionális; ha hiányzik, az src/currentSrc
 *        data-ladym-lb-group="orokolt">    // opcionális; azonos csoport = lapozható galéria
 *
 * Zoom: egérgörgő + dupla katt (PC), pinch + dupla koppintás (mobil). Pásztázás zoomban
 * húzással. Lapozás: nyilak, billentyű (←/→), vagy vízszintes swipe. Bezárás: X, Esc,
 * háttérre katt, vagy lefelé swipe. Nincs külső függőség (nem kell jQuery).
 */
(function () {
  'use strict';

  var SELECTOR = '[data-ladym-lb]';
  var MAX_SCALE = 5;
  var MIN_SCALE = 1;
  var DOUBLE_TAP_MS = 300;
  var SWIPE_THRESHOLD = 60; // px

  var lb = null; // a megosztott lightbox-példány (lustán épül).

  /* ---------------------------------------------------------------- felépítés */

  function buildDom() {
    var root = document.createElement('div');
    root.className = 'ladym-lb';
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-label', 'Képnézegető');
    root.innerHTML =
      '<div class="ladym-lb__stage">' +
      '<img class="ladym-lb__img" alt="" draggable="false" />' +
      '</div>' +
      '<button type="button" class="ladym-lb__btn ladym-lb__close" aria-label="Bezárás">' +
      '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>' +
      '<button type="button" class="ladym-lb__btn ladym-lb__nav ladym-lb__prev" aria-label="Előző kép">' +
      '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>' +
      '<button type="button" class="ladym-lb__btn ladym-lb__nav ladym-lb__next" aria-label="Következő kép">' +
      '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>' +
      '<div class="ladym-lb__counter" aria-hidden="true"></div>';
    document.body.appendChild(root);

    var inst = {
      root: root,
      stage: root.querySelector('.ladym-lb__stage'),
      img: root.querySelector('.ladym-lb__img'),
      counter: root.querySelector('.ladym-lb__counter'),
      items: [],
      index: 0,
      scale: 1,
      tx: 0,
      ty: 0,
      pointers: {}, // pointerId -> {x, y}
      pinch: null, // {dist, midX, midY}
      panLast: null, // {x, y}
      swipeStart: null, // {x, y, t}
      lastTap: 0,
      lastTapX: 0,
      lastTapY: 0,
      lastFocus: null
    };

    wireEvents(inst);
    return inst;
  }

  function applyTransform(inst) {
    inst.img.style.transform =
      'translate(' + inst.tx + 'px,' + inst.ty + 'px) scale(' + inst.scale + ')';
    inst.root.classList.toggle('is-zoomed', inst.scale > 1.001);
  }

  /* Pásztázás korlátozása, hogy a kép ne csússzon ki a képből. */
  function clamp(inst) {
    var sw = inst.stage.clientWidth;
    var sh = inst.stage.clientHeight;
    // clientWidth/Height a transzformáció ELŐTTI (layout) méret – ezzel számolunk.
    var iw = inst.img.clientWidth * inst.scale;
    var ih = inst.img.clientHeight * inst.scale;
    var maxX = Math.max(0, (iw - sw) / 2);
    var maxY = Math.max(0, (ih - sh) / 2);
    inst.tx = Math.max(-maxX, Math.min(maxX, inst.tx));
    inst.ty = Math.max(-maxY, Math.min(maxY, inst.ty));
  }

  /* Zoom egy pont (a színpad közepéhez képesti px) köré. */
  function zoomTo(inst, newScale, px, py) {
    newScale = Math.max(MIN_SCALE, Math.min(MAX_SCALE, newScale));
    var ratio = newScale / inst.scale;
    inst.tx = px - ratio * (px - inst.tx);
    inst.ty = py - ratio * (py - inst.ty);
    inst.scale = newScale;
    if (inst.scale <= 1.001) {
      inst.scale = 1;
      inst.tx = 0;
      inst.ty = 0;
    }
    clamp(inst);
    applyTransform(inst);
  }

  function resetZoom(inst, animate) {
    inst.scale = 1;
    inst.tx = 0;
    inst.ty = 0;
    if (animate) animateOnce(inst);
    applyTransform(inst);
  }

  function animateOnce(inst) {
    inst.img.classList.add('is-animating');
    window.clearTimeout(inst._animT);
    inst._animT = window.setTimeout(function () {
      inst.img.classList.remove('is-animating');
    }, 260);
  }

  /* Színpad-középponthoz viszonyított koordináta egy kliens-pontból. */
  function relToCenter(inst, clientX, clientY) {
    var r = inst.stage.getBoundingClientRect();
    return { x: clientX - (r.left + r.width / 2), y: clientY - (r.top + r.height / 2) };
  }

  /* --------------------------------------------------------------- megnyitás */

  function open(items, index) {
    if (!lb) lb = buildDom();
    var inst = lb;
    inst.items = items;
    inst.index = index || 0;
    inst.lastFocus = document.activeElement;
    inst.root.classList.toggle('is-single', items.length < 2);
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
    show(inst);
    inst.root.classList.add('is-open');
    inst.root.querySelector('.ladym-lb__close').focus();
  }

  function show(inst) {
    var item = inst.items[inst.index];
    resetZoom(inst, false);
    inst.img.src = item.full;
    inst.img.alt = item.alt || '';
    if (inst.items.length > 1) {
      inst.counter.textContent = inst.index + 1 + ' / ' + inst.items.length;
    }
  }

  function go(inst, dir) {
    if (inst.items.length < 2) return;
    inst.index = (inst.index + dir + inst.items.length) % inst.items.length;
    show(inst);
  }

  function close(inst) {
    inst.root.classList.remove('is-open');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    inst.pointers = {};
    inst.pinch = null;
    inst.panLast = null;
    if (inst.lastFocus && inst.lastFocus.focus) inst.lastFocus.focus();
  }

  /* ----------------------------------------------------------------- gesztus */

  function wireEvents(inst) {
    var stage = inst.stage;
    var root = inst.root;

    root.querySelector('.ladym-lb__close').addEventListener('click', function () {
      close(inst);
    });
    root.querySelector('.ladym-lb__prev').addEventListener('click', function (e) {
      e.stopPropagation();
      go(inst, -1);
    });
    root.querySelector('.ladym-lb__next').addEventListener('click', function (e) {
      e.stopPropagation();
      go(inst, 1);
    });

    // Háttérre (nem a képre) kattintás zár.
    stage.addEventListener('click', function (e) {
      if (e.target === stage) close(inst);
    });

    // Görgő-zoom (PC).
    stage.addEventListener(
      'wheel',
      function (e) {
        e.preventDefault();
        var p = relToCenter(inst, e.clientX, e.clientY);
        var factor = e.deltaY < 0 ? 1.18 : 1 / 1.18;
        zoomTo(inst, inst.scale * factor, p.x, p.y);
      },
      { passive: false }
    );

    // Pointer-alapú gesztusok (egér + érintés egységesen).
    stage.addEventListener('pointerdown', function (e) {
      if (e.button && e.button !== 0) return;
      stage.setPointerCapture(e.pointerId);
      inst.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
      var n = Object.keys(inst.pointers).length;

      if (n === 1) {
        inst.panLast = { x: e.clientX, y: e.clientY };
        inst.swipeStart = { x: e.clientX, y: e.clientY, t: now() };
        handleTap(inst, e);
      } else if (n === 2) {
        inst.pinch = pinchState(inst);
        inst.swipeStart = null; // két ujjnál nincs swipe.
      }
    });

    stage.addEventListener('pointermove', function (e) {
      if (!inst.pointers[e.pointerId]) return;
      inst.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
      var n = Object.keys(inst.pointers).length;

      if (n >= 2 && inst.pinch) {
        var ps = pinchState(inst);
        var p = relToCenter(inst, ps.midX, ps.midY);
        // Zoom a pinch-középpont köré.
        zoomTo(inst, inst.scale * (ps.dist / inst.pinch.dist), p.x, p.y);
        // Pásztázás a középpont elmozdulásával.
        inst.tx += ps.midX - inst.pinch.midX;
        inst.ty += ps.midY - inst.pinch.midY;
        clamp(inst);
        applyTransform(inst);
        inst.pinch = ps;
      } else if (n === 1 && inst.scale > 1.001) {
        // Pásztázás zoomban.
        root.classList.add('is-panning');
        inst.tx += e.clientX - inst.panLast.x;
        inst.ty += e.clientY - inst.panLast.y;
        inst.panLast = { x: e.clientX, y: e.clientY };
        clamp(inst);
        applyTransform(inst);
      }
    });

    function endPointer(e) {
      if (!inst.pointers[e.pointerId]) return;
      delete inst.pointers[e.pointerId];
      var n = Object.keys(inst.pointers).length;
      root.classList.remove('is-panning');

      if (n < 2) inst.pinch = null;
      if (n === 1) {
        // Egy ujj maradt: pásztázás folytatása onnan.
        var id = Object.keys(inst.pointers)[0];
        inst.panLast = { x: inst.pointers[id].x, y: inst.pointers[id].y };
      }
      if (n === 0) {
        // Swipe kiértékelése (csak nem-zoomolt állapotban).
        if (inst.swipeStart && inst.scale <= 1.001) {
          var dx = e.clientX - inst.swipeStart.x;
          var dy = e.clientY - inst.swipeStart.y;
          if (Math.abs(dx) > SWIPE_THRESHOLD && Math.abs(dx) > Math.abs(dy)) {
            go(inst, dx < 0 ? 1 : -1);
          } else if (dy > SWIPE_THRESHOLD && dy > Math.abs(dx)) {
            close(inst);
          }
        }
        inst.swipeStart = null;
      }
    }
    stage.addEventListener('pointerup', endPointer);
    stage.addEventListener('pointercancel', endPointer);

    // Billentyűzet.
    document.addEventListener('keydown', function (e) {
      if (!root.classList.contains('is-open')) return;
      if (e.key === 'Escape') close(inst);
      else if (e.key === 'ArrowLeft') go(inst, -1);
      else if (e.key === 'ArrowRight') go(inst, 1);
    });
  }

  function pinchState(inst) {
    var ids = Object.keys(inst.pointers);
    var a = inst.pointers[ids[0]];
    var b = inst.pointers[ids[1]];
    return {
      dist: Math.hypot(b.x - a.x, b.y - a.y) || 1,
      midX: (a.x + b.x) / 2,
      midY: (a.y + b.y) / 2
    };
  }

  /* Dupla katt / dupla koppintás → zoom toggle a megérintett pont körül. */
  function handleTap(inst, e) {
    var t = now();
    var dx = e.clientX - inst.lastTapX;
    var dy = e.clientY - inst.lastTapY;
    if (t - inst.lastTap < DOUBLE_TAP_MS && Math.hypot(dx, dy) < 40) {
      var p = relToCenter(inst, e.clientX, e.clientY);
      animateOnce(inst);
      if (inst.scale > 1.001) resetZoom(inst, true);
      else zoomTo(inst, 2.5, p.x, p.y);
      inst.lastTap = 0;
    } else {
      inst.lastTap = t;
      inst.lastTapX = e.clientX;
      inst.lastTapY = e.clientY;
    }
  }

  function now() {
    return new Date().getTime();
  }

  /* ------------------------------------------------------ trigger-bekötés */

  /* Egy elem -> galéria item (full src + alt). */
  function toItem(el) {
    var full = el.getAttribute('data-ladym-lb-full');
    if (!full) full = el.currentSrc || el.getAttribute('src') || '';
    var alt = el.getAttribute('alt') || el.getAttribute('data-ladym-lb-alt') || '';
    return { full: full, alt: alt, el: el };
  }

  /* A kattintott elem csoportjának összegyűjtése (lapozható galéria). */
  function collectGroup(el) {
    var group = el.getAttribute('data-ladym-lb-group');
    var els;
    if (group) {
      els = Array.prototype.slice.call(
        document.querySelectorAll('[data-ladym-lb-group="' + cssEscape(group) + '"]')
      );
    } else {
      els = [el];
    }
    var items = els.map(toItem);
    var index = els.indexOf(el);
    return { items: items, index: index < 0 ? 0 : index };
  }

  function cssEscape(s) {
    return String(s).replace(/["\\]/g, '\\$&');
  }

  // Delegált kattintás az egész dokumentumon – a később (AJAX-szal) beszúrt
  // képek is működnek, külön inicializálás nélkül.
  document.addEventListener('click', function (e) {
    var el = e.target.closest ? e.target.closest(SELECTOR) : null;
    if (!el) return;
    e.preventDefault();
    var g = collectGroup(el);
    open(g.items, g.index);
  });

  // Billentyűs aktiválás (Enter / Space) a fókuszálható triggereken.
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var el = e.target.closest ? e.target.closest(SELECTOR) : null;
    if (!el) return;
    e.preventDefault();
    var g = collectGroup(el);
    open(g.items, g.index);
  });

  // A triggerek fókuszálhatóvá / „gombbá" tétele (ha a markup nem adta meg).
  function enhance() {
    var els = document.querySelectorAll(SELECTOR);
    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
      if (!el.hasAttribute('role')) el.setAttribute('role', 'button');
      el.classList.add('ladym-zoomable');
    }
  }
  if (document.readyState !== 'loading') enhance();
  else document.addEventListener('DOMContentLoaded', enhance);
})();
