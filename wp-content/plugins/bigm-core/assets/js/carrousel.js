// Big M — carrousels « Nos classiques » : piste qui défile, flèches et points.
// Repris tel quel de prototype/assets/site.js (bloc « Carrousels »).
(() => {
  // Éditeur Elementor : un widget modifié est ré-affiché, on initialise ce qu'il contient.
  const surElementor = (sel, fn) => {
    const brancher = () => window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', ($el) => $el[0].querySelectorAll(sel).forEach(fn));
    if (window.elementorFrontend && window.elementorFrontend.hooks) brancher();
    else window.addEventListener('elementor/frontend/init', brancher);
  };
  // Carrousels : piste qui défile, flèches et points
  const setup = (root) => {
    if (root.dataset.carrouselPret) return;
    root.dataset.carrouselPret = '1';
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
  };
  document.querySelectorAll('[data-carousel]').forEach(setup);
  surElementor('[data-carousel]', setup);
})();
