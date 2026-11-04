// Big M — filtres de la page d'une catégorie : viande, allergènes, message quand rien ne correspond.
// Repris du script de prototype/pages.py (page categorie.html). Seuls changements : le filtre
// travaille dans sa grille ([data-filtres]) et le libellé du bouton vient du balisage.
(() => {
  // Éditeur Elementor : un widget modifié est ré-affiché, on initialise ce qu'il contient.
  const surElementor = (sel, fn) => {
    const brancher = () => window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', ($el) => $el[0].querySelectorAll(sel).forEach(fn));
    if (window.elementorFrontend && window.elementorFrontend.hooks) brancher();
    else window.addEventListener('elementor/frontend/init', brancher);
  };
  const setup = (root) => {
    if (root.dataset.filtresPret) return;
    root.dataset.filtresPret = '1';
    let meat = 'tous';
    const off = new Set();
    const cards = [...root.querySelectorAll('.product')];
    const groups = [...root.querySelectorAll('[data-group]')];
    const empty = root.querySelector('[data-empty]');
    const label = root.querySelector('[data-toggle-allergy]');
    const panel = root.querySelector('.allergy');
    const meats = [...root.querySelectorAll('[data-meat-filter]')];
    const allergens = [...root.querySelectorAll('[data-allergen]')];
    if (!label || !panel) return;
    const apply = () => {
      cards.forEach((c) => {
        const al = c.dataset.allergens.split(' ');
        c.hidden = (meat !== 'tous' && c.dataset.meat !== meat) || al.some((x) => off.has(x));
      });
      let any = false;
      groups.forEach((g) => { const v = [...g.querySelectorAll('.product')].some((c) => !c.hidden); g.hidden = !v; any = any || v; });
      if (empty) empty.dataset.show = String(!any);
      label.textContent = off.size ? 'Allergènes (' + off.size + ')' : label.dataset.libelle;
      label.setAttribute('aria-pressed', String(off.size > 0));
    };
    meats.forEach((b) => b.addEventListener('click', () => {
      meat = b.dataset.meatFilter;
      meats.forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
      apply();
    }));
    allergens.forEach((b) => b.addEventListener('click', () => {
      const k = b.dataset.allergen; off.has(k) ? off.delete(k) : off.add(k);
      b.setAttribute('aria-pressed', String(off.has(k))); apply();
    }));
    label.addEventListener('click', () => { const o = panel.dataset.open !== 'true'; panel.dataset.open = String(o); label.setAttribute('aria-expanded', String(o)); });
    const reset = root.querySelector('[data-reset]');
    if (reset) reset.addEventListener('click', () => {
      meat = 'tous'; off.clear();
      meats.forEach((x) => x.setAttribute('aria-pressed', String(x.dataset.meatFilter === 'tous')));
      allergens.forEach((x) => x.setAttribute('aria-pressed', 'false'));
      apply();
    });
    if (location.hash === '#allergenes' && root.querySelector('#allergenes')) { panel.dataset.open = 'true'; label.setAttribute('aria-expanded', 'true'); }
  };
  document.querySelectorAll('[data-filtres]').forEach(setup);
  surElementor('[data-filtres]', setup);
})();
