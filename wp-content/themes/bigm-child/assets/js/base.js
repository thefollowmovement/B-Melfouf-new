// Big M — JS commun à toutes les pages.
// Repris tel quel de prototype/assets/site.js : choix du format vidéo,
// apparitions au défilement, onde au clic sur les boutons.
// Le tiroir, l'en-tête, les carrousels, le slider et les vignettes vidéo
// suivront avec leur composant.

// Choisit le meilleur format disponible : WebM si le navigateur le lit, sinon MP4.
// Dans WordPress, le WebM est un média à part : son URL arrive en second argument
// (data-src-webm). Sans elle, on garde la règle du prototype (même nom, .webm).
window.bigmVideoSrc = (src, webmSrc) => {
  if (!src) return webmSrc || src;
  const t = document.createElement('video');
  const webm = t.canPlayType('video/webm; codecs="vp9"');
  const mp4 = t.canPlayType('video/mp4; codecs="avc1.42E01E"');
  return (!mp4 && webm) ? (webmSrc || src.replace(/\.mp4$/, '.webm')) : src;
};

// ---------- Apparitions au défilement ----------
(() => {
  const targets = document.querySelectorAll('[data-reveal]');
  if (!('IntersectionObserver' in window)) { targets.forEach((t) => t.classList.add('is-in')); return; }
  const io = new IntersectionObserver((entries) => {
    for (const e of entries) if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
  targets.forEach((t) => io.observe(t));
  window.addEventListener('load', () => setTimeout(() => targets.forEach((t) => { const r = t.getBoundingClientRect(); if (r.top < innerHeight && r.bottom > 0) t.classList.add('is-in'); }), 2000));
})();

// ---------- Boutons : onde qui part de l'endroit touché ----------
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
