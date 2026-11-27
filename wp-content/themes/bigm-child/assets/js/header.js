// Big M — menu latéral (tiroir) et en-tête qui s'affine au défilement.
// Repris de prototype/assets/site.js. Ajouts pour l'accessibilité, sans
// changement visible : le focus reste dans le tiroir ouvert (Tab) et revient
// au bouton qui l'a ouvert quand on le ferme.

// ---------- Menu latéral ----------
(() => {
  const drawer = document.getElementById('drawer');
  const openers = document.querySelectorAll('[data-open-drawer]');
  if (!drawer) return;
  let opener = null;
  const close = () => {
    drawer.dataset.open = 'false';
    document.body.style.overflow = '';
    openers.forEach((o) => o.setAttribute('aria-expanded', 'false'));
    if (opener) { opener.focus(); opener = null; }
  };
  openers.forEach((o) => o.addEventListener('click', () => {
    opener = o;
    drawer.dataset.open = 'true';
    document.body.style.overflow = 'hidden';
    o.setAttribute('aria-expanded', 'true');
    drawer.querySelector('.drawer__close').focus();
  }));
  drawer.addEventListener('click', (e) => {
    if (e.target.closest('a')) { opener = null; close(); return; } // on part vers une autre page
    if (e.target.closest('[data-close-drawer]')) close();
  });
  document.addEventListener('keydown', (e) => {
    if (drawer.dataset.open !== 'true') return;
    if (e.key === 'Escape') { close(); return; }
    if (e.key !== 'Tab') return;
    const items = [...drawer.querySelectorAll('.drawer__panel a[href], .drawer__panel button')];
    const first = items[0], last = items[items.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });
})();

// ---------- En-tête : liseré quand on défile ----------
(() => {
  const h = document.querySelector('.header');
  if (!h) return;
  const f = () => h.classList.toggle('is-scrolled', window.scrollY > 4);
  f(); window.addEventListener('scroll', f, { passive: true });
})();
