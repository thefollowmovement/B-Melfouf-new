// Big M — slider « à la une » et bannières.
// Repris tel quel de prototype/assets/site.js (bloc « Slider à la une »).
// Seul changement : l'URL WebM, qui est un média à part dans WordPress (data-src-webm).
{
  // Éditeur Elementor : un widget modifié est ré-affiché, on initialise ce qu'il contient.
  const surElementor = (sel, fn) => {
    const brancher = () => window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', ($el) => $el[0].querySelectorAll(sel).forEach(fn));
    if (window.elementorFrontend && window.elementorFrontend.hooks) brancher();
    else window.addEventListener('elementor/frontend/init', brancher);
  };
  const pick = (src, webm) => (window.bigmVideoSrc ? window.bigmVideoSrc(src, webm) : src);
  // ---------- Slider « à la une » ----------
  // Slide centrée, voisines visibles, boucle infinie, glisser au doigt avec élan,
  // ressort sans rebond (amortissement 1, réponse 0,45 s), photo ou vidéo par slide.
  (() => {
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const ICON_PAUSE = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>';
    const ICON_PLAY = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5z"/></svg>';

    const setup = (root) => {
      if (root.dataset.hsPret) return;
      root.dataset.hsPret = '1';
      const viewport = root.querySelector('.hs__viewport');
      const track = root.querySelector('.hs__track');
      const real = [...track.children];
      const N = real.length;
      if (!N) return;
      const K = Math.min(2, N);
      const interval = +(root.dataset.interval || 6000);

      // clones pour la boucle
      const mkClone = (el) => { const c = el.cloneNode(true); c.setAttribute('aria-hidden', 'true'); c.inert = true; c.classList.add('is-clone'); c.querySelectorAll('video').forEach((v) => { v.removeAttribute('autoplay'); }); return c; };
      real.slice(-K).reverse().forEach((el) => track.prepend(mkClone(el)));
      real.slice(0, K).forEach((el) => track.append(mkClone(el)));
      const all = [...track.children];
      real.forEach((el, i) => { el.setAttribute('role', 'group'); el.setAttribute('aria-roledescription', 'diapositive'); el.setAttribute('aria-label', (i + 1) + ' sur ' + N); });

      // points
      const dotsBox = root.querySelector('.hs__dots');
      const dots = real.map((_, i) => { const b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Aller à la diapositive ' + (i + 1)); b.addEventListener('click', () => goTo(index + (i - realIndex(index)))); dotsBox.appendChild(b); return b; });

      let index = K, pos = 0, vel = 0, target = 0, raf = 0, step = 0, offset = 0;
      const measure = () => {
        const w = all[0].getBoundingClientRect().width;
        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        step = w + gap;
        offset = (viewport.clientWidth - w) / 2;
      };
      const xFor = (i) => offset - i * step;
      const render = () => { track.style.transform = 'translate3d(' + pos + 'px,0,0)'; };
      const realIndex = (i) => ((i - K) % N + N) % N;

      const setActive = () => {
        const r = realIndex(index);
        all.forEach((el, j) => el.classList.toggle('is-active', j === index));
        dots.forEach((d, j) => d.setAttribute('aria-current', String(j === r)));
        syncVideos();
      };

      // ressort amorti critique : x'' = -w²(x-t) - 2w x'
      const omega = (2 * Math.PI) / 0.45;
      let last = 0;
      const tick = (t) => {
        const dt = Math.min(0.032, (t - last) / 1000 || 0.016); last = t;
        const a = -omega * omega * (pos - target) - 2 * omega * vel;
        vel += a * dt; pos += vel * dt;
        if (Math.abs(pos - target) < 0.3 && Math.abs(vel) < 8) { pos = target; vel = 0; render(); raf = 0; settle(); return; }
        render(); raf = requestAnimationFrame(tick);
      };
      const springTo = (x, v0 = vel) => {
        target = x; vel = v0;
        if (reduce) { pos = x; vel = 0; render(); settle(); return; }
        if (!raf) { last = performance.now(); raf = requestAnimationFrame(tick); }
      };
      const settle = () => {
        // retour silencieux depuis un clone vers la vraie slide
        if (index < K || index >= K + N) {
          const r = realIndex(index);
          index = K + r; pos = target = xFor(index); render();
        }
        setActive();
        schedule();
      };
      const goTo = (i, v0) => {
        const d = i - index;
        if (index >= K + N) { index -= N; pos += N * step; target += N * step; render(); }
        else if (index < K) { index += N; pos -= N * step; target -= N * step; render(); }
        index = Math.max(0, Math.min(all.length - 1, index + d));
        setActive(); springTo(xFor(index), v0);
      };

      // autoplay
      let timer = 0, paused = reduce, hover = false, visible = true;
      const pauseBtn = root.querySelector('.hs__pause');
      const drawPause = () => { pauseBtn.innerHTML = paused ? ICON_PLAY : ICON_PAUSE; pauseBtn.setAttribute('aria-label', paused ? 'Lancer le défilement' : 'Mettre en pause le défilement'); };
      const schedule = () => {
        clearTimeout(timer);
        if (paused || hover || !visible) return;
        const v = all[index].querySelector('video');
        if (v) return; // une vidéo passe à la suivante quand elle se termine
        timer = setTimeout(() => goTo(index + 1), interval);
      };
      const syncVideos = () => {
        all.forEach((el, j) => {
          const v = el.querySelector('video'); if (!v) return;
          if (j === index && visible && !reduce) {
            if (!v.src && v.dataset.src) v.src = pick(v.dataset.src, v.dataset.srcWebm);
            v.loop = paused || hover;
            v.play().catch(() => {});
          } else { v.pause(); }
        });
      };
      real.forEach((el) => { const v = el.querySelector('video'); if (v) v.addEventListener('ended', () => { if (!paused && !hover) goTo(index + 1); else { v.currentTime = 0; v.play().catch(() => {}); } }); });
      pauseBtn.addEventListener('click', () => { paused = !paused; drawPause(); syncVideos(); schedule(); if (paused) all.forEach((el, j) => { const v = el.querySelector('video'); if (v && j === index) v.pause(); }); });
      root.addEventListener('pointerenter', (e) => { if (e.pointerType === 'mouse') { hover = true; clearTimeout(timer); } });
      root.addEventListener('pointerleave', (e) => { if (e.pointerType === 'mouse') { hover = false; syncVideos(); schedule(); } });
      root.addEventListener('focusin', () => { hover = true; clearTimeout(timer); });
      root.addEventListener('focusout', (e) => { if (!root.contains(e.relatedTarget)) { hover = false; schedule(); } });
      if ('IntersectionObserver' in window) new IntersectionObserver(([e]) => { visible = e.isIntersecting; syncVideos(); schedule(); }, { threshold: 0.25 }).observe(root);
      document.addEventListener('visibilitychange', () => { visible = !document.hidden; syncVideos(); schedule(); });

      // flèches et clavier
      root.querySelector('.hs__arrow--prev')?.addEventListener('click', () => goTo(index - 1));
      root.querySelector('.hs__arrow--next')?.addEventListener('click', () => goTo(index + 1));
      root.addEventListener('keydown', (e) => { if (e.key === 'ArrowLeft') { e.preventDefault(); goTo(index - 1); } if (e.key === 'ArrowRight') { e.preventDefault(); goTo(index + 1); } });

      // glisser : suivi 1:1, élan projeté, puis ressort qui reprend la vitesse du doigt
      let drag = null, moved = false;
      viewport.addEventListener('pointerdown', (e) => {
        if (e.button !== 0 || e.target.closest('.hs__arrow')) return;
        if (raf) { cancelAnimationFrame(raf); raf = 0; } // on attrape la slide en vol
        clearTimeout(timer);
        drag = { x: e.clientX, y: e.clientY, start: pos, hist: [[e.clientX, performance.now()]], id: e.pointerId, axis: null };
        moved = false;
      });
      viewport.addEventListener('pointermove', (e) => {
        if (!drag || e.pointerId !== drag.id) return;
        const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
        if (!drag.axis) {
          if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
          drag.axis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
          if (drag.axis === 'y') { drag = null; springTo(xFor(index), 0); return; }
          viewport.setPointerCapture(e.pointerId); viewport.classList.add('is-dragging'); moved = true;
        }
        pos = drag.start + dx; render();
        drag.hist.push([e.clientX, performance.now()]); if (drag.hist.length > 6) drag.hist.shift();
      });
      const end = (e) => {
        if (!drag || e.pointerId !== drag.id) return;
        viewport.classList.remove('is-dragging');
        const h = drag.hist, a = h[0], b = h[h.length - 1];
        const v = b[1] - a[1] > 0 ? ((b[0] - a[0]) / (b[1] - a[1])) * 1000 : 0; // px/s
        drag = null;
        if (!moved) { schedule(); return; }
        const d = 0.99;
        const projected = pos + (v / 1000) * d / (1 - d);
        let i = Math.round((offset - projected) / step);
        i = Math.max(index - 2, Math.min(index + 2, i));
        if (i === index && Math.abs(v) > 300) i += v < 0 ? 1 : -1;
        goTo(i, v);
      };
      viewport.addEventListener('pointerup', end);
      viewport.addEventListener('pointercancel', end);
      // un glisser n'ouvre pas le lien ; un clic sur une voisine la centre
      viewport.addEventListener('click', (e) => {
        if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; return; }
        const s = e.target.closest('.hs__slide');
        if (s && !s.classList.contains('is-active')) { e.preventDefault(); goTo(all.indexOf(s)); }
      }, true);

      const init = () => { measure(); pos = target = xFor(index); render(); };
      window.addEventListener('resize', () => { measure(); pos = target = xFor(index); render(); });
      init(); drawPause(); setActive(); schedule();
    };
    document.querySelectorAll('[data-hs]').forEach(setup);
    surElementor('[data-hs]', setup);
  })();
}
