/**
 * Big M — fenêtre « Commander » (bigm-core, inc/commander.php).
 *
 * Les liens vers le lien Commander par défaut, les liens #commander, ceux qui portent
 * data-bigm-commander et les boutons Commander des restaurants de StoreLocatore ouvrent une
 * fenêtre : choix du restaurant (position ou recherche), puis plateformes et appel.
 *
 * Le restaurant choisi est gardé dans le navigateur sous la clé des promos de StoreLocatore
 * (tfmsl_promo_store) : les promos s'ouvrent sur lui, et un restaurant choisi dans les promos
 * est repris ici. Rien n'est envoyé au serveur. Il devient le restaurant choisi quand le
 * visiteur le choisit dans la liste, par sa position, ou quand il commande dans ce restaurant.
 *
 * Sans <dialog> (vieux navigateurs), les liens gardent leur adresse.
 */
(function () {
  'use strict';

  var cfg = window.bigmCommander || {};
  if (!cfg.api || typeof window.HTMLDialogElement !== 'function') return;

  var CLE = 'tfmsl_promo_store';
  var donnees = null;
  var requete = null;
  var css = null;
  var dlg = null;
  var corps = null;
  var declencheur = null;

  var ICONES = {
    pin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
    viser: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2.5"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>',
    tel: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>',
    sac: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>',
    fleche: '<svg class="bigm-cmd__fleche" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>',
    fermer: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    retour: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>'
  };

  // ── Outils ──────────────────────────────────────────────────────────
  function e(s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  // Même normalisation que la recherche du store locator : accents, casse, tirets, apostrophes.
  function norm(s) {
    return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
      .replace(/œ/g, 'oe').replace(/æ/g, 'ae')
      .replace(/[-‐-―'’‘ʼ`´]/g, ' ').replace(/\s+/g, ' ').trim();
  }
  function cleUrl(u) {
    try { var x = new URL(u, location.href); return (x.origin + x.pathname).replace(/\/+$/, '') + x.search + x.hash; } catch (err) { return ''; }
  }
  function sur(href) { return /^(https?:|tel:)/i.test(String(href || '')); }
  function haversine(a, b, c, d) {
    var r = Math.PI / 180, x = Math.sin((c - a) * r / 2), y = Math.sin((d - b) * r / 2);
    return 12742 * Math.asin(Math.sqrt(x * x + Math.cos(a * r) * Math.cos(c * r) * y * y));
  }
  function km(d) {
    if (d === null || d === undefined || isNaN(d)) return '';
    return (d < 10 ? Math.round(d * 10) / 10 : Math.round(d)).toLocaleString('fr-FR') + ' km';
  }

  // ── Restaurant choisi (clé partagée avec les promos de StoreLocatore) ─
  function lireChoix() {
    try { var v = JSON.parse(window.localStorage.getItem(CLE) || 'null'); return v && v.id ? v : null; } catch (err) { return null; }
  }
  function premierLien(r) {
    var liens = r.liens || {};
    for (var i = 0; i < donnees.plateformes.length; i++) {
      if (liens[donnees.plateformes[i].id]) return liens[donnees.plateformes[i].id];
    }
    return r.autre || '';
  }
  function garder(r) {
    try {
      window.localStorage.setItem(CLE, JSON.stringify({ id: r.id, name: r.nom, short: r.court, order: premierLien(r), url: r.url, dist: typeof r.dist === 'number' ? Math.round(r.dist * 100) / 100 : undefined }));
    } catch (err) { /* stockage indisponible (navigation privée) : le choix vaut pour cette page */ }
  }
  function trouver(id) {
    id = parseInt(id, 10) || 0;
    if (!id || !donnees) return null;
    for (var i = 0; i < donnees.restaurants.length; i++) if (donnees.restaurants[i].id === id) return donnees.restaurants[i];
    return null;
  }

  // ── Chargements : données à la première ouverture, CSS juste avant ───
  function charger() {
    if (donnees) return Promise.resolve(donnees);
    if (!requete) {
      requete = fetch(cfg.api, { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error(String(r.status)); return r.json(); })
        .then(function (d) {
          d.plateformes = d.plateformes || [];
          d.restaurants = d.restaurants || [];
          d.restaurants.forEach(function (r) { r.cle = norm([r.nom, r.ville, r.cp, r.adresse].join(' ')); });
          donnees = d;
          return d;
        })
        .catch(function (err) { requete = null; throw err; });
    }
    return requete;
  }
  function chargerCss() {
    if (!css) {
      css = new Promise(function (ok) {
        if (!cfg.css) { ok(); return; }
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = cfg.css;
        l.onload = l.onerror = function () { ok(); };
        document.head.appendChild(l);
        setTimeout(ok, 2500);
      });
    }
    return css;
  }

  // ── Fenêtre ─────────────────────────────────────────────────────────
  function construire() {
    dlg = document.createElement('dialog');
    dlg.className = 'bigm-cmd';
    dlg.setAttribute('aria-labelledby', 'bigm-cmd-titre');
    dlg.innerHTML = '<div class="bigm-cmd__boite">'
      + '<div class="bigm-cmd__tete"><h2 class="bigm-cmd__titre" id="bigm-cmd-titre"></h2>'
      + '<button type="button" class="bigm-cmd__fermer" aria-label="Fermer">' + ICONES.fermer + '</button></div>'
      + '<div class="bigm-cmd__corps"></div></div>';
    dlg.querySelector('.bigm-cmd__titre').textContent = cfg.titre || 'Commander';
    corps = dlg.querySelector('.bigm-cmd__corps');
    dlg.querySelector('.bigm-cmd__fermer').addEventListener('click', fermer);
    // Clic sur le fond assombri.
    dlg.addEventListener('click', function (ev) { if (ev.target === dlg) fermer(); });
    dlg.addEventListener('close', function () {
      document.documentElement.classList.remove('bigm-cmd-ouverte');
      if (declencheur && document.contains(declencheur)) declencheur.focus({ preventScroll: true });
    });
    document.body.appendChild(dlg);
  }

  function fermer() { if (dlg && dlg.open) dlg.close(); }

  function focus(sel) {
    var cible = corps.querySelector(sel);
    if (cible) cible.focus({ preventScroll: true });
  }

  /**
   * @param {number} id   Restaurant visé par le bouton (fiche StoreLocatore), sinon 0.
   * @param {Element} lien Lien cliqué (repris si la fenêtre ne peut rien proposer).
   */
  function ouvrir(id, lien) {
    declencheur = lien || null;
    if (!dlg) construire();
    corps.innerHTML = '<p class="bigm-cmd__attente" role="status">Chargement des restaurants…</p>';
    chargerCss()
      .then(function () {
        if (!dlg.open) {
          dlg.showModal();
          document.documentElement.classList.add('bigm-cmd-ouverte');
        }
        return charger();
      })
      .then(function () {
        var vise = trouver(id);
        // Rien à choisir (StoreLocatore absent) ou restaurant inconnu : le lien d'origine.
        if ((id && !vise) || !donnees.restaurants.length) { suivre(lien); return; }
        var r = vise || trouver((lireChoix() || {}).id);
        if (r) vueRestaurant(r); else vueChoix();
      })
      .catch(function () {
        corps.innerHTML = '<p class="bigm-cmd__vide" role="alert">Les restaurants n’ont pas pu être chargés. Vérifiez votre connexion.</p>'
          + (sur(cfg.defaut) ? '<p class="bigm-cmd__sans"><a href="' + e(cfg.defaut) + '">Commander sans choisir de restaurant</a></p>' : '');
      });
  }

  function suivre(lien) {
    var href = lien && lien.href && !/#commander$/.test(lien.href) ? lien.href : cfg.defaut;
    fermer();
    if (sur(href)) window.location.href = href;
  }

  function pastille(p) {
    if (p.logo) return '<span class="bigm-cmd__logo bigm-cmd__logo--img"><img src="' + e(p.logo) + '" alt="" width="44" height="44"></span>';
    return '<span class="bigm-cmd__logo" style="--c:' + e(p.couleur || '#2b1406') + '" aria-hidden="true">' + e((p.nom || '?').charAt(0)) + '</span>';
  }

  function option(href, logo, titre, texte, cle) {
    if (!sur(href)) return '';
    return '<li><a class="bigm-cmd__option" data-option="' + e(cle) + '" href="' + e(href) + '">' + logo
      + '<span class="bigm-cmd__opt"><strong>' + e(titre) + '</strong>' + (texte ? '<small>' + e(texte) + '</small>' : '') + '</span>'
      + ICONES.fleche + '</a></li>';
  }

  // ── Vue « restaurant » : ses plateformes et l'appel ─────────────────
  function vueRestaurant(r, focusNom) {
    var options = '';
    donnees.plateformes.forEach(function (p) {
      var lien = r.liens && r.liens[p.id];
      if (lien) options += option(lien, pastille(p), p.nom, p.texte, p.id);
    });
    if (r.autre) options += option(r.autre, '<span class="bigm-cmd__logo bigm-cmd__logo--icone" aria-hidden="true">' + ICONES.sac + '</span>', 'Commander en ligne', '', 'autre');
    if (r.tel) {
      options += option('tel:' + r.tel.replace(/[^\d+]/g, ''), '<span class="bigm-cmd__logo bigm-cmd__logo--icone" aria-hidden="true">' + ICONES.tel + '</span>',
        cfg.telTitre || 'Appeler pour commander', r.tel + (cfg.telTexte ? ' · ' + cfg.telTexte : ''), 'tel');
    }
    var dist = typeof r.dist === 'number' ? km(r.dist) : '';
    var h = '<div class="bigm-cmd__resto">'
      + '<span class="bigm-cmd__pin">' + ICONES.pin + '</span>'
      + '<div class="bigm-cmd__resto-txt"><p class="bigm-cmd__surtitre">Votre restaurant</p>'
      + '<p class="bigm-cmd__nom" tabindex="-1">' + e(r.nom) + '</p>'
      + '<p class="bigm-cmd__adr">' + e(r.adresse || r.ville) + (dist ? ' · ' + e(dist) : '') + '</p></div>'
      + '<button type="button" class="bigm-cmd__changer" data-action="changer">Changer<span class="screen-reader-text"> de restaurant</span></button></div>';
    // Liens du restaurant pas encore saisis : le lien Commander par défaut (page Big M de la plateforme).
    var repli = !options && sur(cfg.defaut);
    if (repli) {
      h += '<p class="bigm-cmd__note">Ce restaurant n’a pas encore ses propres liens de commande.</p>';
      options = option(cfg.defaut, '<span class="bigm-cmd__logo bigm-cmd__logo--icone" aria-hidden="true">' + ICONES.sac + '</span>', 'Commander en ligne', 'Tous les restaurants Big M', 'defaut');
    }
    if (options) {
      h += '<ul class="bigm-cmd__options" aria-label="' + e('Commander à ' + r.nom) + '">' + options + '</ul>';
      if (r.url) h += '<p class="bigm-cmd__fiche"><a href="' + e(r.url) + '">Horaires et adresse du restaurant</a></p>';
    } else {
      h += '<p class="bigm-cmd__vide">Ce restaurant ne prend pas encore de commandes en ligne.'
        + (r.url ? ' <a href="' + e(r.url) + '">Voir ses horaires et son adresse</a>' : '') + '</p>';
    }
    corps.innerHTML = h;
    corps.querySelectorAll('[data-option]').forEach(function (a) {
      a.addEventListener('click', function () { garder(r); });
    });
    corps.querySelector('[data-action="changer"]').addEventListener('click', function () { vueChoix(true); });
    if (focusNom) focus('.bigm-cmd__nom'); else focus('.bigm-cmd__option, [data-action="changer"]');
  }

  // ── Vue « choix du restaurant » : position ou recherche ────────────
  function vueChoix(depuisRestaurant) {
    var choisi = trouver((lireChoix() || {}).id);
    var h = '';
    if (cfg.texte) h += '<p class="bigm-cmd__intro">' + e(cfg.texte) + '</p>';
    h += '<button type="button" class="btn bigm-cmd__geo" data-action="geo">' + ICONES.viser + 'Me localiser</button>'
      + '<p class="bigm-cmd__statut" role="status"></p>'
      + '<label class="bigm-cmd__label" for="bigm-cmd-q">Ou cherchez une ville, un code postal</label>'
      + '<input class="bigm-cmd__q" id="bigm-cmd-q" type="search" autocomplete="off" enterkeyhint="search" aria-controls="bigm-cmd-liste">'
      + '<ul class="bigm-cmd__liste" id="bigm-cmd-liste" aria-label="Restaurants"></ul>'
      + '<p class="bigm-cmd__aucun" role="status"></p>';
    if (choisi) h += '<p class="bigm-cmd__retour"><button type="button" data-action="retour">' + ICONES.retour + e('Garder ' + choisi.nom) + '</button></p>';
    if (sur(cfg.defaut)) h += '<p class="bigm-cmd__sans"><a href="' + e(cfg.defaut) + '">Commander sans choisir de restaurant</a></p>';
    corps.innerHTML = h;

    var q = corps.querySelector('.bigm-cmd__q');
    var liste = corps.querySelector('.bigm-cmd__liste');
    var aucun = corps.querySelector('.bigm-cmd__aucun');
    var statut = corps.querySelector('.bigm-cmd__statut');

    function afficher() {
      var terme = norm(q.value);
      var items = donnees.restaurants.filter(function (r) { return !terme || r.cle.indexOf(terme) > -1; });
      if (items.some(function (r) { return typeof r.dist === 'number'; })) {
        items = items.slice().sort(function (a, b) {
          return (typeof a.dist === 'number' ? a.dist : 1e9) - (typeof b.dist === 'number' ? b.dist : 1e9);
        });
      }
      liste.innerHTML = items.map(function (r) {
        var dist = typeof r.dist === 'number' ? km(r.dist) : '';
        var sous = [r.cp && r.ville ? r.cp + ' ' + r.ville : r.ville, dist].filter(Boolean).join(' · ');
        return '<li><button type="button" class="bigm-cmd__item" data-id="' + r.id + '"' + (choisi && choisi.id === r.id ? ' aria-current="true"' : '') + '>'
          + '<span class="bigm-cmd__item-nom">' + e(r.nom) + '</span>' + (sous ? '<span class="bigm-cmd__item-adr">' + e(sous) + '</span>' : '')
          + '</button></li>';
      }).join('');
      aucun.textContent = items.length ? '' : 'Aucun restaurant ne correspond à cette recherche.';
    }

    q.addEventListener('input', afficher);
    q.addEventListener('keydown', function (ev) {
      // Entrée : le premier restaurant de la liste.
      if (ev.key !== 'Enter') return;
      ev.preventDefault();
      var premier = liste.querySelector('.bigm-cmd__item');
      if (premier) premier.click();
    });
    liste.addEventListener('click', function (ev) {
      var b = ev.target.closest('.bigm-cmd__item');
      if (!b) return;
      var r = trouver(b.getAttribute('data-id'));
      if (r) { garder(r); vueRestaurant(r, true); }
    });
    corps.querySelector('[data-action="geo"]').addEventListener('click', function () { localiser(this, statut, q); });
    var retour = corps.querySelector('[data-action="retour"]');
    if (retour) retour.addEventListener('click', function () { vueRestaurant(choisi, true); });

    afficher();
    if (depuisRestaurant) focus('.bigm-cmd__q'); else focus('[data-action="geo"]');
  }

  function localiser(bouton, statut, q) {
    if (!navigator.geolocation) {
      statut.textContent = 'La localisation n’est pas disponible. Cherchez votre ville ci-dessous.';
      q.focus();
      return;
    }
    statut.textContent = 'Recherche de votre position…';
    bouton.setAttribute('aria-busy', 'true');
    bouton.disabled = true;
    navigator.geolocation.getCurrentPosition(function (pos) {
      var lat = pos.coords.latitude, lng = pos.coords.longitude;
      donnees.restaurants.forEach(function (r) {
        r.dist = typeof r.lat === 'number' && typeof r.lng === 'number' ? haversine(lat, lng, r.lat, r.lng) : null;
      });
      var proches = donnees.restaurants.filter(function (r) { return typeof r.dist === 'number'; })
        .sort(function (a, b) { return a.dist - b.dist; });
      if (!proches.length) {
        bouton.disabled = false;
        bouton.removeAttribute('aria-busy');
        statut.textContent = 'Aucun restaurant trouvé près de vous. Cherchez votre ville ci-dessous.';
        return;
      }
      garder(proches[0]);
      vueRestaurant(proches[0], true);
    }, function () {
      bouton.disabled = false;
      bouton.removeAttribute('aria-busy');
      statut.textContent = 'Position introuvable. Cherchez votre ville ci-dessous.';
      q.focus();
    }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 600000 });
  }

  // ── Liens qui ouvrent la fenêtre ────────────────────────────────────
  var cibles = (cfg.cibles || [cfg.defaut]).filter(Boolean).map(cleUrl);
  function cible(a) {
    return a.hasAttribute('data-bigm-commander') || a.hash === '#commander' || cibles.indexOf(cleUrl(a.href)) > -1;
  }

  document.addEventListener('click', function (ev) {
    if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
    var a = ev.target.closest ? ev.target.closest('a[href]') : null;
    if (!a || (dlg && dlg.contains(a))) return;
    var id = 0;
    if (a.getAttribute('data-tfmsl-track') === 'click_order' && a.getAttribute('data-tfmsl-store')) {
      id = parseInt(a.getAttribute('data-tfmsl-store'), 10) || 0;
    } else if (!cible(a)) {
      return;
    }
    ev.preventDefault();
    ouvrir(id, a);
  });

  // Charger les données dès que le visiteur s'approche d'un bouton Commander.
  function prechargerSi(ev) {
    var a = ev.target.closest ? ev.target.closest('a[href]') : null;
    if (a && cible(a)) {
      charger().catch(function () {});
      chargerCss();
    }
  }
  document.addEventListener('pointerover', prechargerSi, { passive: true });
  document.addEventListener('focusin', prechargerSi);

  // Adresse partagée « …/#commander » : la fenêtre s'ouvre à l'arrivée.
  if (location.hash === '#commander') ouvrir(0, null);
})();
