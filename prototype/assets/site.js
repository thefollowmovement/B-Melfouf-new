// Choisit le meilleur format disponible : WebM si le navigateur le lit, sinon MP4.
window.bigmVideoSrc = (src) => {
  if (!src) return src;
  const t = document.createElement('video');
  const webm = t.canPlayType('video/webm; codecs="vp9"');
  const mp4 = t.canPlayType('video/mp4; codecs="avc1.42E01E"');
  return (!mp4 && webm) ? src.replace(/\.mp4$/, '.webm') : src;
};
// Big M — menu latéral, carrousels, apparitions.
(() => {
  // Menu latéral
  const drawer = document.getElementById('drawer');
  const openers = document.querySelectorAll('[data-open-drawer]');
  if (drawer) {
    const close = () => { drawer.dataset.open = 'false'; document.body.style.overflow = ''; openers.forEach((o) => o.setAttribute('aria-expanded', 'false')); };
    openers.forEach((o) => o.addEventListener('click', () => { drawer.dataset.open = 'true'; document.body.style.overflow = 'hidden'; o.setAttribute('aria-expanded', 'true'); drawer.querySelector('.drawer__close').focus(); }));
    drawer.addEventListener('click', (e) => { if (e.target.closest('[data-close-drawer]') || e.target.closest('a')) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && drawer.dataset.open === 'true') close(); });
  }

  // Carrousels : piste qui défile, flèches et points
  document.querySelectorAll('[data-carousel]').forEach((root) => {
    const track = root.querySelector('[data-track]');
    const prev = root.querySelector('[data-prev]');
    const next = root.querySelector('[data-next]');
    const dots = [...root.querySelectorAll('[data-dot]')];
    const slides = [...track.children];
    const step = () => (slides[1] ? slides[1].offsetLeft - slides[0].offsetLeft : track.clientWidth);
    const index = () => Math.round(track.scrollLeft / step());
    const go = (i) => track.scrollTo({ left: Math.max(0, Math.min(i, slides.length - 1)) * step(), behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    prev && prev.addEventListener('click', () => go(index() - 1));
    next && next.addEventListener('click', () => go(index() + 1));
    dots.forEach((d, i) => d.addEventListener('click', () => go(i)));
    const sync = () => {
      const i = index();
      dots.forEach((d, j) => d.setAttribute('aria-current', String(i === j)));
      if (prev) prev.disabled = track.scrollLeft < 4;
      if (next) next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
    };
    track.addEventListener('scroll', () => requestAnimationFrame(sync), { passive: true });
    window.addEventListener('resize', sync);
    sync();
    if (root.hasAttribute('data-autoplay') && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
      let timer = setInterval(() => { const i = index(); go(i >= slides.length - 1 ? 0 : i + 1); }, 6000);
      root.addEventListener('pointerenter', () => clearInterval(timer));
      root.addEventListener('focusin', () => clearInterval(timer));
    }
  });

  // Apparitions au défilement
  const targets = document.querySelectorAll('[data-reveal]');
  if (!('IntersectionObserver' in window)) { targets.forEach((t) => t.classList.add('is-in')); return; }
  const io = new IntersectionObserver((entries) => {
    for (const e of entries) if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
  targets.forEach((t) => io.observe(t));
  window.addEventListener('load', () => setTimeout(() => targets.forEach((t) => { const r = t.getBoundingClientRect(); if (r.top < innerHeight && r.bottom > 0) t.classList.add('is-in'); }), 2000));
})();

// ---------- En-tête : liseré quand on défile ----------
(() => {
  const h = document.querySelector('.header');
  if (!h) return;
  const f = () => h.classList.toggle('is-scrolled', window.scrollY > 4);
  f(); window.addEventListener('scroll', f, { passive: true });
})();

// ---------- Slider « à la une » ----------
// Slide centrée, voisines visibles, boucle infinie, glisser au doigt avec élan,
// ressort sans rebond (amortissement 1, réponse 0,45 s), photo ou vidéo par slide.
(() => {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const ICON_PAUSE = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>';
  const ICON_PLAY = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5z"/></svg>';

  document.querySelectorAll('[data-hs]').forEach((root) => {
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
          if (!v.src && v.dataset.src) v.src = window.bigmVideoSrc(v.dataset.src);
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
  });
})();

// ---------- Vignettes avec vidéo de fond ----------
// Lecture automatique, en boucle et sans son, dès que la vignette est visible ; pause hors écran.
(() => {
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const vids = [...document.querySelectorAll('video[data-tile-video]')];
  const play = (v) => {
    if (!v.src) { v.src = window.bigmVideoSrc(v.dataset.src); v.load(); }
    v.play().then(() => v.classList.add('is-playing')).catch(() => {});
  };
  const stop = (v) => v.pause();
  if (!('IntersectionObserver' in window)) { vids.forEach(play); return; }
  const io = new IntersectionObserver((es) => es.forEach((e) => (e.isIntersecting ? play(e.target) : stop(e.target))), { rootMargin: '120px 0px', threshold: 0.15 });
  vids.forEach((v) => { v.addEventListener('playing', () => v.classList.add('is-playing')); io.observe(v); });
  document.addEventListener('visibilitychange', () => { if (document.hidden) vids.forEach(stop); });
})();

// ---------- Boutons : reflet qui suit le doigt / la souris ----------
(() => {
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  document.addEventListener('pointerdown', (e) => {
    const b = e.target.closest('.btn, .tabbar__fab');
    if (!b) return;
    const r = b.getBoundingClientRect();
    const dot = document.createElement('span');
    dot.className = 'ripple';
    const size = Math.max(r.width, r.height) * 2.2;
    dot.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX - r.left - size / 2}px;top:${e.clientY - r.top - size / 2}px`;
    b.appendChild(dot);
    dot.addEventListener('animationend', () => dot.remove());
  });
})();
