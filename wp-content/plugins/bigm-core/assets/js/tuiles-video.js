// Big M — vidéo de fond des vignettes (produits, catégories).
// Repris tel quel de prototype/assets/site.js. Seul changement : l'URL WebM séparée.
{
  // Éditeur Elementor : un widget modifié est ré-affiché, on initialise ce qu'il contient.
  const surElementor = (sel, fn) => {
    const brancher = () => window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', ($el) => $el[0].querySelectorAll(sel).forEach(fn));
    if (window.elementorFrontend && window.elementorFrontend.hooks) brancher();
    else window.addEventListener('elementor/frontend/init', brancher);
  };
  const pick = (src, webm) => (window.bigmVideoSrc ? window.bigmVideoSrc(src, webm) : src);
  // ---------- Vignettes avec vidéo de fond ----------
  // Lecture automatique, en boucle et sans son, dès que la vignette est visible ; pause hors écran.
  (() => {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const vids = [...document.querySelectorAll('video[data-tile-video]')];
    const play = (v) => {
      if (!v.src) { v.src = pick(v.dataset.src, v.dataset.srcWebm); v.load(); }
      v.play().then(() => v.classList.add('is-playing')).catch(() => {});
    };
    const stop = (v) => v.pause();
    if (!('IntersectionObserver' in window)) { vids.forEach(play); return; }
    const io = new IntersectionObserver((es) => es.forEach((e) => (e.isIntersecting ? play(e.target) : stop(e.target))), { rootMargin: '120px 0px', threshold: 0.15 });
    const watch = (v) => {
      if (v.dataset.videoPret) return;
      v.dataset.videoPret = '1';
      if (!vids.includes(v)) vids.push(v);
      v.addEventListener('playing', () => v.classList.add('is-playing')); io.observe(v);
    };
    vids.forEach(watch);
    surElementor('video[data-tile-video]', watch);
    document.addEventListener('visibilitychange', () => { if (document.hidden) vids.forEach(stop); });
  })();
}
