// Big M — bandeau de consentement aux cookies (extension bigm-cookies).
// Le choix est gardé dans le cookie « bigm_cookies » : {v: version du texte, d: date, mesure: 0|1,
// contenus: 0|1}. Sans choix valable (absent, expiré, autre version), le bandeau s'affiche et rien
// n'est chargé. Une finalité acceptée relance ses scripts en attente
// (script[type="text/plain"][data-bigm-cookies]) et affiche ses contenus intégrés.
(() => {
  const racine = document.getElementById('bigm-cookies');
  if (!racine) return;
  const NOM = 'bigm_cookies';
  const version = racine.dataset.version || '1';
  const duree = parseInt(racine.dataset.duree, 10) || 182;
  const cases = [...racine.querySelectorAll('[data-bigm-cookies-cat]')];
  const finalites = cases.map((c) => c.dataset.bigmCookiesCat);
  const panneau = racine.querySelector('#bigm-cookies-choix');
  const bouton = (action) => racine.querySelector(`[data-bigm-cookies-action="${action}"]`);
  const titre = racine.querySelector('#bigm-cookies-titre');
  const statut = racine.querySelector('.bigm-cookies__statut');
  const actives = new Set();
  let declencheur = null;

  const lire = () => {
    const m = document.cookie.match(/(?:^|;\s*)bigm_cookies=([^;]*)/);
    if (!m) return null;
    try {
      const c = JSON.parse(decodeURIComponent(m[1]));
      return c && c.v === version ? c : null;
    } catch (e) {
      return null;
    }
  };

  const ecrire = (choix) => {
    const c = { v: version, d: new Date().toISOString().slice(0, 10) };
    finalites.forEach((f) => { c[f] = choix[f] ? 1 : 0; });
    document.cookie = `${NOM}=${encodeURIComponent(JSON.stringify(c))}; Max-Age=${duree * 86400}; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
    return c;
  };

  // Contenu intégré mis en attente par l'extension : code d'origine en base64 (UTF-8).
  const afficherContenu = (encart) => {
    let html = '';
    try {
      html = new TextDecoder().decode(Uint8Array.from(atob(encart.dataset.contenu || ''), (ch) => ch.charCodeAt(0)));
    } catch (e) {
      return;
    }
    const modele = document.createElement('template');
    modele.innerHTML = html;
    // Les scripts insérés par innerHTML ne s'exécutent pas : on les recrée.
    modele.content.querySelectorAll('script').forEach((ancien) => {
      const s = document.createElement('script');
      [...ancien.attributes].forEach((a) => s.setAttribute(a.name, a.value));
      s.text = ancien.text;
      ancien.replaceWith(s);
    });
    encart.replaceWith(modele.content);
  };

  const activer = (finalite) => {
    if (actives.has(finalite)) return;
    actives.add(finalite);
    if (finalite === 'mesure' && typeof window.gtag === 'function') {
      window.gtag('consent', 'update', { analytics_storage: 'granted' });
    }
    if (typeof window.wp_set_consent === 'function') {
      window.wp_set_consent(finalite === 'mesure' ? 'statistics' : 'marketing', 'allow');
    }
    document.querySelectorAll(`script[type="text/plain"][data-bigm-cookies="${finalite}"]`).forEach((ancien) => {
      const s = document.createElement('script');
      [...ancien.attributes].forEach((a) => {
        if (a.name !== 'type' && a.name !== 'data-bigm-cookies') s.setAttribute(a.name, a.value);
      });
      if (ancien.src) {
        if (!ancien.hasAttribute('async')) s.async = false; // garde l'ordre des scripts
      } else {
        s.text = ancien.text;
      }
      ancien.replaceWith(s);
    });
    document.querySelectorAll(`[data-bigm-cookies-contenu="${finalite}"]`).forEach(afficherContenu);
  };

  // Cookies de Google Analytics à effacer quand l'accord est retiré.
  const effacerMesure = () => {
    const domaines = ['', location.hostname, `.${location.hostname.replace(/^www\./, '')}`];
    document.cookie.split(';').map((c) => c.split('=')[0].trim()).filter((n) => /^(_ga|_gid|_gat)/.test(n)).forEach((n) => {
      domaines.forEach((d) => {
        document.cookie = `${n}=; Max-Age=0; Path=/${d ? `; Domain=${d}` : ''}`;
      });
    });
  };

  const montrer = (parUtilisateur) => {
    const c = lire();
    cases.forEach((caseFinalite) => { caseFinalite.checked = !!(c && c[caseFinalite.dataset.bigmCookiesCat]); });
    racine.hidden = false;
    document.documentElement.classList.add('bigm-cookies-ouvert');
    if (parUtilisateur) {
      ouvrirPanneau(true);
      titre.focus();
    }
  };

  const cacher = () => {
    racine.hidden = true;
    document.documentElement.classList.remove('bigm-cookies-ouvert');
    ouvrirPanneau(false);
    if (declencheur && document.contains(declencheur)) declencheur.focus();
    declencheur = null;
  };

  const ouvrirPanneau = (ouvert) => {
    panneau.hidden = !ouvert;
    bouton('personnaliser').setAttribute('aria-expanded', String(ouvert));
    bouton('personnaliser').hidden = ouvert;
    bouton('enregistrer').hidden = !ouvert;
  };

  // Enregistre un choix et l'applique. Un accord retiré (déjà appliqué dans la page) demande de
  // recharger la page, pour que les scripts déjà lancés s'arrêtent.
  const choisir = (choix) => {
    const retire = finalites.some((f) => actives.has(f) && !choix[f]);
    const c = ecrire(choix);
    if (retire) {
      if (!c.mesure) effacerMesure();
      statut.textContent = 'Choix enregistré, la page se recharge.';
      location.reload();
      return;
    }
    finalites.forEach((f) => { if (c[f]) activer(f); });
    cacher();
  };

  const tout = (valeur) => Object.fromEntries(finalites.map((f) => [f, valeur]));

  racine.addEventListener('click', (e) => {
    const b = e.target.closest('[data-bigm-cookies-action]');
    if (!b) return;
    const action = b.dataset.bigmCookiesAction;
    if (action === 'accepter') choisir(tout(true));
    else if (action === 'refuser') choisir(tout(false));
    else if (action === 'personnaliser') {
      ouvrirPanneau(true);
      (cases[0] || titre).focus();
    } else if (action === 'enregistrer') {
      choisir(Object.fromEntries(cases.map((c) => [c.dataset.bigmCookiesCat, c.checked])));
    }
  });

  // Rouvrir le bandeau : lien « Paramétrer les cookies » (#gestion-cookies), bouton du code court.
  document.addEventListener('click', (e) => {
    const lien = e.target.closest('a[href$="#gestion-cookies"], [data-bigm-cookies-ouvrir]');
    if (lien) {
      e.preventDefault();
      declencheur = lien;
      montrer(true);
      return;
    }
    // « Afficher le contenu » d'un contenu intégré : accord pour les contenus d'autres sites.
    const autoriser = e.target.closest('[data-bigm-cookies-autoriser]');
    if (autoriser) {
      const f = autoriser.dataset.bigmCookiesAutoriser;
      const c = lire() || {};
      choisir({ ...tout(false), ...Object.fromEntries(finalites.map((x) => [x, !!c[x]])), [f]: true });
    }
  });

  // Échap ferme le bandeau seulement s'il a été rouvert (un choix existe déjà).
  racine.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && declencheur && lire()) cacher();
  });

  window.bigmCookies = {
    accepte: (finalite) => !!(lire() || {})[finalite],
    ouvrir: () => montrer(true),
  };

  const c = lire();
  if (c) {
    finalites.forEach((f) => { if (c[f]) activer(f); });
  } else {
    montrer(false);
  }
  if (location.hash === '#gestion-cookies') montrer(true);
})();
