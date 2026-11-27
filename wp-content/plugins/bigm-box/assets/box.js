// Big M — Nos Box : les cartes sont des onglets, le visuel de la Box choisie s'affiche.
// Clic ou toucher, flèches du clavier, Début / Fin. Sur mobile, la carte choisie
// est ramenée dans la bande qui défile. Aucune dépendance.
(() => {
  const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

  const setup = (root) => {
    if (root.dataset.bigmBoxesPret) return;
    root.dataset.bigmBoxesPret = '1';
    const list = root.querySelector('[data-bigm-boxes-tabs]');
    const tabs = [...root.querySelectorAll('[role="tab"]')];
    const panels = tabs.map((t) => document.getElementById(t.getAttribute('aria-controls')));
    if (!list || !tabs.length) return;

    // Carte choisie visible dans la bande, sans faire défiler la page.
    const reveal = (tab) => {
      if (list.scrollWidth <= list.clientWidth + 2) return;
      const left = tab.offsetLeft - (list.clientWidth - tab.offsetWidth) / 2;
      list.scrollTo({ left: Math.max(0, left), behavior: reduce() ? 'auto' : 'smooth' });
    };

    const select = (i, focus) => {
      tabs.forEach((tab, j) => {
        const on = i === j;
        tab.setAttribute('aria-selected', String(on));
        tab.tabIndex = on ? 0 : -1;
        const panel = panels[j];
        if (!panel) return;
        if (on && panel.hidden) {
          panel.hidden = false;
          panel.classList.remove('is-entering');
          void panel.offsetWidth; // relance l'animation
          panel.classList.add('is-entering');
        } else if (!on) {
          panel.hidden = true;
        }
      });
      if (focus) tabs[i].focus({ preventScroll: true });
      reveal(tabs[i]);
    };

    tabs.forEach((tab, i) => tab.addEventListener('click', () => select(i, false)));
    list.addEventListener('keydown', (e) => {
      const i = tabs.indexOf(document.activeElement);
      if (i < 0) return;
      const n = tabs.length;
      const to = { ArrowRight: i + 1, ArrowDown: i + 1, ArrowLeft: i - 1, ArrowUp: i - 1, Home: 0, End: n - 1 }[e.key];
      if (to === undefined) return;
      e.preventDefault();
      select((to + n) % n, true);
    });

    // Visuels des autres Box chargés à l'approche de la section : le changement est immédiat.
    // Seulement les Box cachées : toucher l'image affichée pendant son chargement la ferait recharger.
    const warm = () => root.querySelectorAll('[role="tabpanel"][hidden] .bigm-boxes__visual img[loading="lazy"]').forEach((img) => { img.loading = 'eager'; });
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) { warm(); io.disconnect(); }
      }, { rootMargin: '400px 0px' });
      io.observe(root);
    } else {
      warm();
    }

    // Apparition au défilement (data-reveal du thème) : le thème ne surveille que les
    // éléments présents au chargement ; un widget ré-affiché par Elementor est surveillé ici.
    if (root.hasAttribute('data-reveal') && !root.classList.contains('is-in')) {
      if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
          if (entries.some((e) => e.isIntersecting)) { root.classList.add('is-in'); io.disconnect(); }
        }, { threshold: 0.05 });
        io.observe(root);
      } else {
        root.classList.add('is-in');
      }
    }
  };

  const all = () => document.querySelectorAll('[data-bigm-boxes]').forEach(setup);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', all);
  else all();

  // Éditeur Elementor : un widget modifié est ré-affiché, on initialise ce qu'il contient.
  const brancher = () => window.elementorFrontend.hooks.addAction('frontend/element_ready/bigm-box.default', ($el) => $el[0].querySelectorAll('[data-bigm-boxes]').forEach(setup));
  if (window.elementorFrontend && window.elementorFrontend.hooks) brancher();
  else window.addEventListener('elementor/frontend/init', brancher);
})();
