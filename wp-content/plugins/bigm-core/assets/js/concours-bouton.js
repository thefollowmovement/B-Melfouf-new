// Big M — bouton flottant « Jeu concours en cours » (inc/concours.php). Montré pendant le jeu
// (dates lues dans data-debut / data-fin : pages en cache), après le bandeau des cookies, sauf si
// le visiteur l'a fermé (bigm_jeu_ferme_{id} dans le stockage du navigateur).
(() => {
  const b = document.querySelector('[data-jc-flottant]');
  if (!b) return;
  const cle = `bigm_jeu_ferme_${b.dataset.id}`;
  let ferme = false;
  try { ferme = localStorage.getItem(cle) === '1'; } catch (e) { /* stockage indisponible : on montre */ }
  const debut = b.dataset.debut ? Date.parse(b.dataset.debut) : 0;
  const fin = b.dataset.fin ? Date.parse(b.dataset.fin) : 0;
  const maintenant = Date.now();
  if (ferme || (debut && maintenant < debut) || (fin && maintenant > fin)) return;

  // « Plus que 13 jours », « Dernier jour ».
  const reste = b.querySelector('[data-jc-reste]');
  if (reste && fin) {
    const jours = Math.floor((fin - maintenant) / 86400000); // comme le compte à rebours de la page
    reste.textContent = jours < 1 ? 'Dernier jour pour jouer' : `Plus que ${jours} jour${jours > 1 ? 's' : ''} pour jouer`;
  }

  const reduit = matchMedia('(prefers-reduced-motion: reduce)').matches;
  let pret = false; // après le bandeau des cookies
  let fermeIci = false;
  let banniereVue = false; // bannière du jeu à l'écran (accueil) : le bouton s'efface
  const maj = () => {
    const voir = pret && !fermeIci && !banniereVue;
    if (voir && b.hidden) {
      b.hidden = false;
      requestAnimationFrame(() => requestAnimationFrame(() => b.classList.add('is-visible')));
    } else if (!voir && !b.hidden) {
      b.classList.remove('is-visible');
      setTimeout(() => { if (!b.classList.contains('is-visible')) b.hidden = true; }, reduit ? 0 : 350);
    }
  };
  const montrer = () => { pret = true; maj(); };
  const bannieres = document.querySelectorAll('[data-jc-bande]');
  if (bannieres.length && 'IntersectionObserver' in window) {
    const vues = new Set();
    new IntersectionObserver((es) => {
      es.forEach((e) => { if (e.isIntersecting && !e.target.hidden) vues.add(e.target); else vues.delete(e.target); });
      banniereVue = vues.size > 0;
      maj();
    }, { threshold: 0.2 }).observe(bannieres[0]);
  }
  // Pas par-dessus le bandeau des cookies : on attend qu'il soit fermé.
  const html = document.documentElement;
  if (html.classList.contains('bigm-cookies-ouvert')) {
    const o = new MutationObserver(() => {
      if (!html.classList.contains('bigm-cookies-ouvert')) { o.disconnect(); setTimeout(montrer, 600); }
    });
    o.observe(html, { attributes: true, attributeFilter: ['class'] });
  } else {
    setTimeout(montrer, 1200);
  }

  b.querySelector('[data-jc-fermer]').addEventListener('click', () => {
    try { localStorage.setItem(cle, '1'); } catch (e) { /* fermé pour cette page seulement */ }
    fermeIci = true;
    maj();
  });

  // Le jeu se termine pendant la visite : le bouton s'en va.
  if (fin) setTimeout(() => { fermeIci = true; maj(); }, Math.min(fin - maintenant + 1000, 2147483000));
})();
