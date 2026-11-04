// Big M — restaurants (plugin StoreLocatore de l'agence), sans modifier le plugin :
// - la recherche de l'accueil (/restaurants/?q=bondy) est appliquée au store locator ;
// - la recherche trouve aussi « saint ouen » ou « creteil » (sans tiret ni accent) ;
// - le champ de recherche, sans étiquette dans le plugin, est nommé pour les lecteurs d'écran.
(() => {
  const plat = (t) => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[-'’]/g, ' ').replace(/\s+/g, ' ').trim();
  const q = (new URLSearchParams(location.search).get('q') || '').trim();
  const champs = () => [...document.querySelectorAll('.tfmsl-locator-search-input, #tfmsl-store-search')];
  const preparer = () => {
    // Le plugin cherche le texte saisi dans data-search : on y ajoute la version sans accents ni tirets.
    document.querySelectorAll('.tfmsl-locator-card[data-search]').forEach((c) => {
      if (c.dataset.bigmPlat) return;
      c.dataset.bigmPlat = '1';
      c.dataset.search += ' ' + plat(c.dataset.search);
    });
    champs().forEach((i) => {
      if (!i.getAttribute('aria-label')) i.setAttribute('aria-label', i.placeholder || 'Rechercher un restaurant');
    });
  };
  // Le store locator branche sa recherche au chargement de la page : on lance la nôtre après.
  const chercher = () => {
    if (!q) return;
    champs().forEach((i) => { i.value = q; i.dispatchEvent(new Event('input', { bubbles: true })); });
  };
  preparer();
  document.addEventListener('DOMContentLoaded', () => { preparer(); chercher(); });
  window.addEventListener('load', chercher);
})();
