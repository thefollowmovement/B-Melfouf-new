// Big M — jeux concours (inc/concours.php) : compte à rebours et état du jeu, vidéo, compteurs des
// lots, recherche des restaurants participants. Les pages sont en cache : tout ce qui dépend de
// l'heure se décide ici, d'après data-debut et data-fin.
(() => {
  const reduit = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const pick = (src, webm) => (window.bigmVideoSrc ? window.bigmVideoSrc(src, webm) : (src || webm));
  const deux = (n) => String(n).padStart(2, '0');
  const pluriel = (n, mot) => `${n} ${mot}${n > 1 ? 's' : ''}`;

  // ---------- État du jeu et compte à rebours ----------
  const jeu = (bloc) => {
    const debut = bloc.dataset.debut ? Date.parse(bloc.dataset.debut) : 0;
    const fin = bloc.dataset.fin ? Date.parse(bloc.dataset.fin) : 0;
    const compte = bloc.querySelector('[data-jc-compte]');
    const label = bloc.querySelector('[data-jc-label]');
    const etat = bloc.querySelector('[data-jc-etat]');
    const lu = bloc.querySelector('[data-jc-sr]');
    const cases = {};
    ['j', 'h', 'm', 's'].forEach((u) => { cases[u] = compte && compte.querySelector(`[data-jc-${u}]`); });
    let minuteur = 0;
    let phasePrec = '';
    const poser = (el, v) => {
      if (!el || el.textContent === v) return;
      el.textContent = v;
      if (!reduit) { el.classList.remove('is-tic'); void el.offsetWidth; el.classList.add('is-tic'); }
    };
    const maj = () => {
      const now = Date.now();
      const phase = debut && now < debut ? 'avant' : (fin && now > fin ? 'apres' : 'pendant');
      if (phase !== phasePrec) {
        phasePrec = phase;
        bloc.classList.toggle('jc--a_venir', phase === 'avant');
        bloc.classList.toggle('jc--en_cours', phase === 'pendant');
        bloc.classList.toggle('jc--termine', phase === 'apres');
        if (bloc.hasAttribute('data-jc-bande')) bloc.hidden = phase !== 'pendant';
        if (etat && etat.dataset[phase]) etat.textContent = etat.dataset[phase];
        if (label && label.dataset[phase]) label.textContent = label.dataset[phase];
        bloc.querySelectorAll('[data-jc-jouer]').forEach((a) => { a.hidden = phase !== 'pendant'; });
        const fini = bloc.querySelector('[data-jc-fini]');
        if (fini) fini.hidden = phase !== 'apres';
        if (compte) compte.classList.toggle('is-fini', phase === 'apres');
      }
      if (!compte || phase === 'apres') { clearInterval(minuteur); return; }
      let reste = Math.max(0, Math.floor(((phase === 'avant' ? debut : fin) - now) / 1000));
      const j = Math.floor(reste / 86400); reste %= 86400;
      const h = Math.floor(reste / 3600); reste %= 3600;
      const m = Math.floor(reste / 60);
      const s = reste % 60;
      poser(cases.j, String(j));
      poser(cases.h, deux(h));
      poser(cases.m, deux(m));
      poser(cases.s, deux(s));
      // Pour les lecteurs d'écran, une phrase à la minute près (pas d'annonce automatique).
      if (lu) {
        const t = `${label ? label.textContent : ''} ${pluriel(j, 'jour')}, ${pluriel(h, 'heure')} et ${pluriel(m, 'minute')}.`;
        if (lu.textContent !== t) lu.textContent = t;
      }
    };
    maj();
    if (compte && phasePrec !== 'apres') minuteur = setInterval(maj, 1000);
    // La bande d'une fiche apparaît ou disparaît aussi au bon moment si la page reste ouverte.
    else if (bloc.hasAttribute('data-jc-bande')) minuteur = setInterval(maj, 60000);
  };

  // ---------- Vidéo : lecture sans son quand elle est visible, pause hors écran ----------
  const video = (v) => {
    const boite = v.parentNode;
    const bPause = boite.querySelector('[data-jc-pause]');
    const bSon = boite.querySelector('[data-jc-son]');
    // Mouvement réduit : rien ne démarre seul. Bande d'une fiche sur mobile : l'affiche seulement.
    const petiteBande = !!v.closest('[data-jc-bande]') && matchMedia('(max-width: 760px)').matches;
    if (petiteBande) return;
    let enPause = reduit;
    let visible = false;
    const charger = () => { if (!v.getAttribute('src')) { v.src = pick(v.dataset.src, v.dataset.srcWebm); } };
    const lire = () => { charger(); v.play().catch(() => {}); };
    const boutons = () => {
      if (bPause) {
        bPause.setAttribute('aria-pressed', String(enPause));
        bPause.setAttribute('aria-label', enPause ? 'Lire la vidéo' : 'Mettre la vidéo en pause');
      }
      if (bSon) {
        bSon.setAttribute('aria-pressed', String(!v.muted));
        bSon.setAttribute('aria-label', v.muted ? 'Activer le son' : 'Couper le son');
      }
    };
    if (bPause) bPause.addEventListener('click', () => { enPause = !enPause; if (enPause) v.pause(); else lire(); boutons(); });
    if (bSon) bSon.addEventListener('click', () => { v.muted = !v.muted; if (!v.muted) { enPause = false; lire(); } boutons(); });
    v.addEventListener('playing', () => v.classList.add('is-playing'));
    if ('IntersectionObserver' in window) {
      new IntersectionObserver((es) => es.forEach((e) => {
        visible = e.isIntersecting;
        if (visible && !enPause) lire(); else v.pause();
      }), { threshold: 0.25 }).observe(v);
    } else if (!enPause) {
      lire();
    }
    document.addEventListener('visibilitychange', () => { if (document.hidden) v.pause(); else if (visible && !enPause) lire(); });
    boutons();
  };

  // ---------- Lots : les nombres défilent jusqu'à leur valeur à l'arrivée ----------
  const compteurs = (racine) => {
    if (reduit || !('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((es) => es.forEach((e) => {
      if (!e.isIntersecting) return;
      io.unobserve(e.target);
      const cible = parseInt(e.target.dataset.jcCompteur, 10) || 0;
      if (cible < 2) return;
      const t0 = performance.now();
      const duree = 700 + Math.min(cible, 40) * 15;
      const pas = (t) => {
        const p = Math.min(1, (t - t0) / duree);
        e.target.textContent = String(Math.round(cible * (1 - (1 - p) ** 3)));
        if (p < 1) requestAnimationFrame(pas);
      };
      requestAnimationFrame(pas);
    }), { threshold: 0.6 });
    racine.querySelectorAll('[data-jc-compteur]').forEach((el) => io.observe(el));
  };

  // ---------- Restaurants participants : recherche sans accents ni tirets ----------
  const norm = (s) => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .replace(/œ/g, 'oe').replace(/æ/g, 'ae').replace(/[-‐-―'’‘ʼ`´]/g, ' ').replace(/\s+/g, ' ').trim();
  const recherche = (racine) => {
    const champ = racine.querySelector('[data-jc-q]');
    if (!champ) return;
    const items = [...racine.querySelectorAll('[data-jc-resto]')].map((li) => ({ li, q: norm(li.dataset.q) }));
    const vide = racine.querySelector('[data-jc-vide]');
    champ.addEventListener('input', () => {
      const q = norm(champ.value);
      let n = 0;
      items.forEach((it) => { const ok = !q || it.q.includes(q); it.li.hidden = !ok; if (ok) n += 1; });
      if (vide) vide.hidden = n > 0;
    });
  };

  const init = (racine) => {
    racine.querySelectorAll('[data-jc]').forEach((bloc) => {
      if (bloc.dataset.jcPret) return;
      bloc.dataset.jcPret = '1';
      jeu(bloc);
      bloc.querySelectorAll('video[data-jc-video]').forEach(video);
      compteurs(bloc);
      recherche(bloc);
    });
  };
  init(document);

  // Éditeur Elementor : un widget modifié est ré-affiché.
  const brancher = () => window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', ($el) => init($el[0]));
  if (window.elementorFrontend && window.elementorFrontend.hooks) brancher();
  else window.addEventListener('elementor/frontend/init', brancher);
})();
