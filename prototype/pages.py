from build import ICON, STORES, tabs, TABS_CSS

def a(i):
    return ICON[i]

# ------------------------------------------------------------------ ACCUEIL
HOME_CSS = '''
.page-title { font-size: clamp(30px, 3.4vw, 44px); margin: 28px 0 22px; }
.news { position: relative; }
.news__track { display: grid; grid-auto-flow: column; grid-auto-columns: min(72%, 900px); gap: 20px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; padding-bottom: 4px; }
.news__track::-webkit-scrollbar { display: none; }
.slide { scroll-snap-align: start; display: flex; flex-direction: column; gap: 14px; text-decoration: none; }
.slide__img { aspect-ratio: 16 / 9; border-radius: var(--radius); overflow: hidden; background: var(--tile); }
.slide__img img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s var(--ease); }
.slide:hover .slide__img img { transform: scale(1.03); }
.slide__cap { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.slide__cap b { font-family: var(--display); font-weight: 600; font-size: 22px; }
.slide__cap span { font-family: var(--display); font-weight: 600; color: var(--btn); display: inline-flex; gap: 6px; align-items: center; white-space: nowrap; }
.carousel-ctrl { display: flex; align-items: center; justify-content: space-between; margin-top: 18px; }
.dots { display: flex; gap: 8px; }
.dots button { width: 10px; height: 10px; border-radius: 99px; background: rgba(43,20,6,.25); transition: width .25s var(--ease), background-color .2s ease; }
.dots button[aria-current="true"] { width: 32px; background: var(--btn); }
.arrows { display: flex; gap: 10px; }
.arrows button { width: 48px; height: 48px; border-radius: 50%; background: #fff; display: inline-flex; align-items: center; justify-content: center; transition: background-color .18s ease, opacity .18s ease; }
.arrows button:hover:not(:disabled) { background: var(--tile); }
.arrows button:disabled { opacity: .35; cursor: default; }

.app-band { background: var(--orange); border-radius: 28px; display: grid; grid-template-columns: 0.9fr 1.1fr; align-items: center; gap: 24px; overflow: hidden; padding: 0 clamp(24px, 4vw, 56px); }
.app-band__img { align-self: end; padding-top: 32px; }
.app-band__img img { width: min(100%, 440px); margin-inline: auto; margin-bottom: -40px; }
.app-band__text { display: flex; flex-direction: column; gap: 16px; padding-block: 48px; }
.app-band__text h2 { font-size: clamp(30px, 3.4vw, 46px); color: #fff; }
.app-band__text p { font-size: 19px; color: var(--ink); max-width: 40ch; }
.app-band__text ul { display: flex; flex-direction: column; gap: 8px; font-weight: 600; }
.app-band__text li { display: flex; gap: 10px; align-items: center; }
.app-band__text li::before { content: ""; width: 10px; height: 10px; border-radius: 50%; background: var(--ink); flex: 0 0 auto; }

.restos { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; align-items: stretch; }
.restos__search { background: #fff; border-radius: 28px; padding: clamp(24px, 4vw, 48px); display: flex; flex-direction: column; gap: 18px; justify-content: center; }
.restos__search h2 { font-size: clamp(30px, 3.4vw, 44px); }
.restos__search label { font-weight: 600; }
.search { display: flex; gap: 10px; }
.search input { flex: 1 1 auto; min-width: 0; min-height: 52px; border-radius: 12px; border: 2px solid rgba(43,20,6,.2); padding: 0 16px; background: var(--bg); font-size: 16px; }
.search input:focus { outline: none; border-color: var(--ink); }
.restos__links { display: flex; flex-wrap: wrap; gap: 12px 24px; }
.restos__photo { border-radius: 28px; overflow: hidden; position: relative; min-height: 360px; }
.restos__photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.restos__photo figcaption { position: absolute; left: 16px; bottom: 16px; background: #fff; border-radius: 999px; padding: 6px 14px; font-weight: 600; font-size: 14px; }

.services { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
.service { background: #fff; border-radius: var(--radius); overflow: hidden; display: flex; flex-direction: column; text-decoration: none; }
.service__img { aspect-ratio: 4 / 3; overflow: hidden; }
.service__img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s var(--ease); }
.service:hover .service__img img { transform: scale(1.04); }
.service__body { padding: 20px 22px 24px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
.service__body h3 { font-size: 24px; }
.service__body p { color: var(--muted); }
.service__body .link { margin-top: auto; padding-top: 6px; }

.classics__track { display: grid; grid-auto-flow: column; grid-auto-columns: calc((100% - 4 * 18px) / 5); gap: 18px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; }
.classics__track::-webkit-scrollbar { display: none; }
.classics__track .tile { scroll-snap-align: start; }

.legends { background: var(--tile); }
.legends__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; }
.legend { display: flex; flex-direction: column; gap: 12px; }
.legend b { font-family: var(--display); font-weight: 600; font-size: 34px; color: var(--btn); line-height: 1; }
.legend img { border-radius: var(--radius); aspect-ratio: 9 / 16; object-fit: cover; width: 100%; }
.legend span { font-weight: 600; }

.news-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
.news-card { background: #fff; border-radius: var(--radius); overflow: hidden; text-decoration: none; display: flex; flex-direction: column; }
.news-card__img { aspect-ratio: 16 / 9; overflow: hidden; }
.news-card__img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s var(--ease); }
.news-card:hover .news-card__img img { transform: scale(1.04); }
.news-card__body { padding: 18px 20px 22px; display: flex; flex-direction: column; gap: 8px; }
.news-card__body small { color: var(--muted); font-size: 14px; }
.news-card__body h3 { font-size: 21px; line-height: 1.2; }

.join { display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 20px; }
.join__card { border-radius: 28px; overflow: hidden; position: relative; min-height: 420px; display: flex; flex-direction: column; justify-content: flex-end; }
.join__card img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.join__card::after { content: ""; position: absolute; inset: 0; background: rgba(20, 10, 4, .42); }
.join__body { position: relative; z-index: 1; padding: clamp(24px, 3.5vw, 44px); color: #fff; display: flex; flex-direction: column; gap: 14px; align-items: flex-start; }
.join__body h2 { font-size: clamp(30px, 3.2vw, 44px); }
.join__body p { max-width: 42ch; font-size: 18px; }
.figures { display: flex; gap: 12px; flex-wrap: wrap; }
.figures span { background: rgba(255,255,255,.16); border-radius: 12px; padding: 8px 14px; font-weight: 600; }
.figures b { font-family: var(--display); font-size: 20px; }

@media (max-width: 960px) {
  .news__track { grid-auto-columns: 88%; }
  .app-band { grid-template-columns: 1fr; }
  .app-band__img { order: 2; }
  .app-band__text { padding-bottom: 8px; }
  .restos { grid-template-columns: minmax(0, 1fr); }
  .search { flex-wrap: wrap; }
  .search .btn { flex: 1 1 auto; }
  .services, .news-cards { grid-template-columns: 1fr; }
  .classics__track { grid-auto-columns: 46%; }
  .legends__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .join { grid-template-columns: 1fr; }
}
'''

def hs_slide(kind, src, alt, href, pos='50% 50%', poster=''):
    """Une slide : kind = 'img' ou 'video'. Pour une vidéo, src = fichier mp4, poster = image affichée avant lecture."""
    if kind == 'video':
        media = f'<video class="hs__media" data-src="assets/video/{src}" poster="assets/video/{poster}" muted playsinline preload="none" aria-label="{alt}" style="object-position:{pos}"></video>'
    else:
        media = f'<img class="hs__media" src="assets/img/{src}" alt="{alt}" style="object-position:{pos}" draggable="false">'
    return f'<div class="hs__slide"><a href="{href}" draggable="false">{media}</a></div>'

def hs(slides, label, variant='', interval=6000):
    return f"""<div class="hs {variant}" data-hs data-interval="{interval}" aria-roledescription="carrousel" aria-label="{label}">
  <div class="hs__viewport">
    <div class="hs__track">{''.join(hs_slide(*x) for x in slides)}</div>
    <button class="hs__arrow hs__arrow--prev" type="button" aria-label="Diapositive précédente">{a('left')}</button>
    <button class="hs__arrow hs__arrow--next" type="button" aria-label="Diapositive suivante">{a('right')}</button>
  </div>
  <div class="hs__bar"><div class="hs__dots"></div><button class="hs__pause" type="button"></button></div>
</div>"""

def tile(img, name, href='produit.html', alt='', video=''):
    v = f'<video data-tile-video data-src="assets/video/{video}" muted loop playsinline preload="none" aria-hidden="true"></video>' if video else ''
    cls = 'tile has-video' if video else 'tile'
    return f'<a class="{cls}" href="{href}"><div class="tile__img"><img src="assets/img/{img}" alt="{alt}" width="600" height="600" loading="lazy">{v}</div><span class="tile__label">{name}</span></a>'

HOME_SLIDES = [
    ('video', 'hero-streetcola.mp4', 'Big M, c\'est ça qu\'on aime : trois burgers au naan et le Street Cola', 'carte.html#boissons', '50% 50%', 'hero-streetcola-poster.jpg'),
    ('img', 'b-offre-doubles.jpg', 'Menu Doublés : deux burgers, frites et boisson', 'categorie.html', '50% 30%'),
    ('img', 'b-offre-texmex.jpg', 'Tex-Mex : tenders, nuggets et frites', 'carte.html#tex-mex', '50% 30%'),
    ('img', 'b-offre-menu-simple.jpg', 'Menu Simple : smash naan, frites et boisson', 'produit.html', '50% 30%'),
    ('img', 'b-franchise-poignee.jpg', 'Nouveaux restaurants Big M ouverts en co-franchise', '#actus', '50% 40%'),
    ('img', 'b-mhsc-maillot.jpg', 'Big M, partenaire officiel du Montpellier Hérault SC', '#actus', '50% 40%'),
]

HOME = f'''<main>
<section aria-labelledby="t-news" style="padding-top: 20px">
  <h1 class="sr-only" id="t-news">L'actualité Big M</h1>
  {hs(HOME_SLIDES, "L'actualité Big M")}
</section>

<section class="container section" id="appli" aria-labelledby="t-app">
  <div class="app-band" data-reveal>
    <div class="app-band__img"><img src="assets/img/app-phone.webp" alt="L'application Big M sur un iPhone" width="504" height="599" loading="lazy"></div>
    <div class="app-band__text">
      <h2 id="t-app">Profitez d'offres exclusives</h2>
      <p>Commandez sur l'application Big M auprès de votre restaurant préféré.</p>
      <ul><li>Livraison ou click &amp; collect</li><li>5&#8239;€ offerts tous les 100&#8239;€ d'achat</li><li>Des offres réservées à l'appli</li></ul>
      {STORES}
    </div>
  </div>
</section>

<section class="container" id="restaurants" aria-labelledby="t-restos">
  <div class="restos">
    <form class="restos__search" action="#restaurants" onsubmit="event.preventDefault()" data-reveal>
      <h2 id="t-restos">Tous nos restaurants</h2>
      <label for="adresse">Entrez une adresse pour trouver le Big M le plus proche</label>
      <div class="search">
        <input id="adresse" name="adresse" type="text" placeholder="Ville, code postal ou adresse" autocomplete="street-address">
        <button class="btn" type="submit">Rechercher</button>
      </div>
      <div class="restos__links">
        <a class="link" href="#restaurants">{a('pin')} Autour de moi</a>
        <a class="link" href="#restaurants">Voir tous les restaurants {a('arrow')}</a>
      </div>
    </form>
    <figure class="restos__photo" data-reveal style="--i:1"><img src="assets/img/b-restaurant-saint-ouen.jpg" alt="Le comptoir du Big M de Saint-Ouen" width="1390" height="868" loading="lazy"><figcaption>Big M Saint-Ouen</figcaption></figure>
  </div>
</section>

<section class="container section" aria-labelledby="t-services">
  <div class="section-head"><h2 id="t-services">Choisissez le service qui vous ressemble</h2></div>
  <div class="services">
    <a class="service" href="#restaurants" data-reveal><div class="service__img"><img src="assets/img/b-restaurant-saint-ouen.jpg" alt="" width="1390" height="868" loading="lazy" style="object-position: 30% 50%"></div><div class="service__body"><h3>Sur place</h3><p>Installez-vous dans le Big M le plus proche.</p><span class="link">Trouver un restaurant {a('arrow')}</span></div></a>
    <a class="service" href="#appli" data-reveal style="--i:1"><div class="service__img"><img src="assets/img/b-offre-menu-simple.jpg" alt="" width="672" height="448" loading="lazy"></div><div class="service__body"><h3>Click &amp; collect</h3><p>Commandez sur l'appli, récupérez votre menu sans attendre.</p><span class="link">Commander {a('arrow')}</span></div></a>
    <a class="service" href="#appli" data-reveal style="--i:2"><div class="service__img"><img src="assets/img/b-street-cola.jpg" alt="" width="1456" height="818" loading="lazy"></div><div class="service__body"><h3>Livraison</h3><p>Vos burgers livrés chez vous, avec les offres de l'appli.</p><span class="link">Se faire livrer {a('arrow')}</span></div></a>
  </div>
</section>

<section class="container" aria-labelledby="t-classics">
  <div class="section-head">
    <div><h2 id="t-classics">Découvrez nos classiques</h2><p>Grand, géant, au poulet ou à la flamme&#8239;: il y en a pour tous les goûts.</p></div>
    <a class="btn btn--outline" href="carte.html">Voir la carte</a>
  </div>
  <div data-carousel>
    <div class="classics__track" data-track>
      {tile('p-bigm.jpg','Le Big M', video='tile-bigm.mp4')}{tile('p-giant.jpg','Le Giant')}{tile('p-tower.jpg','Le Tower', video='tile-tower.mp4')}{tile('p-jumbo.jpg','Le Double Jumbo', video='tile-jumbo.mp4')}{tile('p-smashnaan.jpg','Smash Naan')}{tile('p-chicken.jpg','Chicken Burger')}{tile('p-tenders.jpg','Tenders')}{tile('p-nuggets.jpg','Nuggets')}{tile('p-streetcola.jpg','Street Cola')}
    </div>
    <div class="carousel-ctrl"><span></span><div class="arrows"><button type="button" data-prev aria-label="Précédent">{a('left')}</button><button type="button" data-next aria-label="Suivant">{a('right')}</button></div></div>
  </div>
</section>

<section class="legends section" id="legendes" aria-labelledby="t-legends" style="margin-top: clamp(48px, 7vw, 88px)">
  <div class="container">
    <div class="section-head"><div><h2 id="t-legends">Big M les a réunis pour vous</h2><p>Né à Bondy en 2019, Big M est l'un des pionniers du fast-food halal en France. L'idée&#8239;: réunir le meilleur du burger au même endroit.</p></div></div>
    <div class="legends__grid">
      <div class="legend" data-reveal><b>1967</b><img src="assets/img/poster-grand.jpg" alt="Affiche : certains les préfèrent grand" width="818" height="1456" loading="lazy"><span>Certains les préfèrent grand</span></div>
      <div class="legend" data-reveal style="--i:1"><b>1971</b><img src="assets/img/poster-geant.jpg" alt="Affiche : d'autres les aiment géant" width="818" height="1456" loading="lazy"><span>D'autres les aiment géant</span></div>
      <div class="legend" data-reveal style="--i:2"><b>1993</b><img src="assets/img/poster-poulet.jpg" alt="Affiche : la plupart au poulet" width="818" height="1456" loading="lazy"><span>La plupart au poulet</span></div>
      <div class="legend" data-reveal style="--i:3"><b>1957</b><img src="assets/img/poster-flamme.jpg" alt="Affiche : et pour d'autres à la flamme" width="818" height="1456" loading="lazy"><span>Et pour d'autres, à la flamme</span></div>
    </div>
  </div>
</section>

<section class="container section" id="actus" aria-labelledby="t-actus">
  <div class="section-head"><h2 id="t-actus">Actualités et presse</h2><a class="link" href="#actus">Toutes les actualités {a('arrow')}</a></div>
  <div class="news-cards">
    <a class="news-card" href="#actus" data-reveal><div class="news-card__img"><img src="assets/img/b-franchise-poignee.jpg" alt="Deux associés se serrent la main dans un restaurant Big M" width="1456" height="817" loading="lazy"></div><div class="news-card__body"><small>Presse · 9 octobre 2026</small><h3>France Snacking&#8239;: «&#8239;Ça vient d'ouvrir&#8239;!&#8239;»</h3></div></a>
    <a class="news-card" href="#actus" data-reveal style="--i:1"><div class="news-card__img"><img src="assets/img/b-street-cola.jpg" alt="Le Street Cola de Big M" width="1456" height="818" loading="lazy"></div><div class="news-card__body"><small>News · 20 septembre 2024</small><h3>Big M révolutionne la street food avec son Street Cola</h3></div></a>
    <a class="news-card" href="#actus" data-reveal style="--i:2"><div class="news-card__img"><img src="assets/img/b-mhsc-maillot.jpg" alt="Le logo Big M sur le maillot du MHSC" width="1456" height="817" loading="lazy"></div><div class="news-card__body"><small>News · 15 novembre 2023</small><h3>Big M devient partenaire officiel du Montpellier HSC</h3></div></a>
  </div>
</section>

<section class="container" aria-label="Nous rejoindre">
  <div class="join">
    <div class="join__card" id="franchise" data-reveal>
      <img src="assets/img/b-franchise-poignee.jpg" alt="" width="1456" height="817" loading="lazy">
      <div class="join__body">
        <h2>Devenez franchisé Big M</h2>
        <p>Créez, transformez ou reprenez un restaurant avec un concept clé en main, une formation complète et des campagnes TV.</p>
        <div class="figures"><span><b>5 ans</b> de contrat</span><span><b>30&#8239;000&#8239;€&#8239;HT</b> d'entrée</span><span><b>5&#8239;%</b> de redevance</span></div>
        <a class="btn" href="#franchise">Déposer ma candidature</a>
      </div>
    </div>
    <div class="join__card" id="recrutement" data-reveal style="--i:1">
      <img src="assets/img/b-chicken-gants.jpg" alt="" width="1256" height="836" loading="lazy">
      <div class="join__body">
        <h2>Recrutez-nous&#8239;!</h2>
        <p>Équipiers, managers, formateurs&#8239;: rejoignez Big M près de chez vous.</p>
        <a class="btn btn--light" href="#recrutement">Postuler</a>
      </div>
    </div>
  </div>
</section>
</main>
'''

# ------------------------------------------------------------------ CARTE
CARTE_CSS = TABS_CSS + '''
.carte-head { padding-block: 36px 24px; display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; }
.carte-head h1 { font-size: clamp(36px, 4.4vw, 56px); }
.carte-head p { color: var(--muted); margin-top: 8px; }
.cat-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 20px; }
.cat-grid .tile { scroll-margin-top: 200px; }
.allergy-band { margin-top: 48px; background: var(--ink); color: #fff; border-radius: 28px; padding: clamp(24px, 4vw, 44px); display: flex; justify-content: space-between; align-items: center; gap: 24px; flex-wrap: wrap; }
.allergy-band h2 { font-size: clamp(26px, 2.8vw, 36px); color: #fff; }
.allergy-band p { max-width: 60ch; margin-top: 8px; color: #f6e4d6; }
@media (max-width: 960px) { .cat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; } }
'''

def cat_tile(n, i, img, lab, h, video=''):
    v = f'<video data-tile-video data-src="assets/video/{video}" muted loop playsinline preload="none" aria-hidden="true"></video>' if video else ''
    cls = 'tile has-video' if video else 'tile'
    return f'<a class="{cls}" id="{i}" href="{h}" data-reveal style="--i:{n % 5}"><div class="tile__img"><img src="assets/img/{img}" alt="" width="600" height="600" loading="lazy">{v}</div><span class="tile__label">{lab}</span></a>'

CARTE_BANNERS = [
    ('video', 'banner-doubles.mp4', 'Menu Doublés : deux burgers, frites et boisson', 'categorie.html', '50% 50%', 'banner-doubles-poster.jpg'),
    ('img', 'b-street-cola.jpg', 'Le Street Cola de Big M', 'carte.html#boissons', '50% 50%'),
    ('img', 'b-offre-texmex.jpg', 'Nos Tex-Mex', 'carte.html#tex-mex', '50% 55%'),
]

CAT_TILES = [
    ('nouveautes', 'p-smashnaan.jpg', 'Nouveautés', 'produit.html'),
    ('menus', 'p-menus.jpg', 'Menus', 'categorie.html'),
    ('burgers', 'p-bigm.jpg', 'Burgers doublés', 'categorie.html', 'tile-bigm.mp4'),
    ('enflammes', 'p-jumbo.jpg', 'Burgers enflammés', 'categorie.html', 'tile-jumbo.mp4'),
    ('poulet', 'p-tower.jpg', 'Poulet', 'categorie.html#poulet', 'tile-tower.mp4'),
    ('sandwichs', 'p-giant.jpg', 'Sandwichs à la carte', 'categorie.html'),
    ('baos', 'p-chicken.jpg', 'Baos', 'categorie.html'),
    ('tex-mex', 'p-tenders.jpg', 'Tex-Mex', 'categorie.html'),
    ('petite-faim', 'p-nuggets.jpg', 'Petite faim', 'categorie.html'),
    ('boissons', 'p-streetcola.jpg', 'Desserts &amp; boissons', 'categorie.html'),
]

CARTE = f'''{tabs('all')}
<main class="container">
  <div class="carte-head">
    <div><h1>Notre carte</h1><p>Tous nos produits sont 100&#8239;% halal. La carte peut varier selon les restaurants.</p></div>
    <a class="btn" href="index.html#appli">{a('bag')}Commander</a>
  </div>
</main>
<section aria-label="Offres du moment" style="margin-bottom: 32px">{hs(CARTE_BANNERS, 'Offres du moment', 'hs--banner', 5000)}</section>
<main class="container">
  <div class="cat-grid">
    {''.join(cat_tile(n, *t) for n, t in enumerate(CAT_TILES))}
  </div>
  <section class="allergy-band" aria-labelledby="t-allerg" data-reveal>
    <div><h2 id="t-allerg">Allergènes</h2><p>Chaque fiche produit indique les 14 allergènes à déclaration obligatoire. Dans chaque catégorie, filtrez les produits selon vos allergies.</p></div>
    <a class="btn btn--light" href="categorie.html#allergenes">Filtrer par allergène</a>
  </section>
</main>
'''

# ------------------------------------------------------------------ CATEGORIE
CATEG_CSS = TABS_CSS + '''
.carte-head { padding-block: 32px 20px; }
.carte-head h1 { font-size: clamp(36px, 4.4vw, 56px); }
.carte-head p { color: var(--muted); margin-top: 8px; }
.filters { margin-top: 28px; }
.cat-banner { margin-top: 28px; border-radius: 28px; overflow: hidden; position: relative; aspect-ratio: 1240 / 360; background: var(--orange); }
.cat-banner img { position: absolute; right: 0; top: 0; height: 100%; width: 56%; object-fit: cover; object-position: 50% 85%; }
.cat-banner__text { position: relative; z-index: 1; height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 10px; padding: 0 clamp(24px, 4vw, 52px); max-width: 42%; }
.cat-banner h1 { font-size: clamp(36px, 5vw, 68px); color: #fff; }
.cat-banner p { font-size: 18px; color: var(--ink); font-weight: 600; }
.filters { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin: 28px 0 8px; }
.chips { display: flex; gap: 10px; flex-wrap: wrap; }
.chip { min-height: 44px; padding: 0 18px; border-radius: 999px; background: #fff; box-shadow: inset 0 0 0 1.5px rgba(43,20,6,.35); font-weight: 600; transition: background-color .18s ease, color .18s ease; }
.chip:hover { background: var(--tile); }
.chip[aria-pressed="true"] { background: var(--ink); color: #fff; box-shadow: none; }
.allergy { background: #fff; border-radius: var(--radius); padding: 22px; margin-top: 14px; display: none; flex-direction: column; gap: 14px; }
.allergy[data-open="true"] { display: flex; }
.allergy h2 { font-size: 22px; }
.allergy .chip[aria-pressed="true"] { background: var(--btn); text-decoration: line-through; }
.allergy small { color: var(--muted); }
.group { margin-top: 36px; }
.group h2 { font-size: 30px; margin-bottom: 16px; }
.products { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; }
.product { background: #fff; border-radius: var(--radius); padding: 16px; display: flex; flex-direction: column; gap: 12px; text-decoration: none; position: relative; transition: transform .2s var(--ease); }
.product:hover { transform: translateY(-3px); }
.product:hover .product__name { color: var(--btn); }
.product__name { font-family: var(--display); font-weight: 600; font-size: 20px; line-height: 1.15; min-height: 2.3em; }
.product__img { aspect-ratio: 1 / 1; border-radius: var(--radius-sm); overflow: hidden; position: relative; }
.product__img img { width: 100%; height: 100%; object-fit: cover; }
.badge { position: absolute; z-index: 1; top: 10px; left: 10px; background: var(--yellow); border-radius: 999px; padding: 2px 10px; font-weight: 600; font-size: 13px; }
.empty { display: none; margin-top: 36px; background: #fff; border-radius: var(--radius); padding: 32px; text-align: center; }
.empty[data-show="true"] { display: block; }
[hidden] { display: none !important; }
@media (max-width: 960px) {
  .cat-banner { aspect-ratio: auto; display: flex; flex-direction: column; }
  .cat-banner img { position: static; width: 100%; height: 200px; }
  .cat-banner__text { max-width: none; padding-block: 24px; order: -1; }
  .products { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
  .product { padding: 12px; }
  .product__name { font-size: 17px; }
}
'''

CATEG_BANNERS = [
    ('video', 'banner-doubles.mp4', 'Menu Doublés : deux burgers, frites et boisson', 'categorie.html', '50% 50%', 'banner-doubles-poster.jpg'),
    ('img', 'b-offre-menu-simple.jpg', 'Menu Simple : smash naan, frites et boisson', 'produit.html', '50% 60%'),
    ('img', 'b-street-cola.jpg', 'Le Street Cola', 'carte.html#boissons', '50% 50%'),
]

PRODUCTS = [
    ('boeuf', 'Le Big M', 'p-bigm.jpg', '', 'gluten lait moutarde sesame oeufs', 'tile-bigm.mp4'),
    ('boeuf', 'Le Giant', 'p-giant.jpg', '', 'gluten lait moutarde sesame'),
    ('boeuf', 'Le Double Jumbo', 'p-jumbo.jpg', '', 'gluten lait moutarde sesame', 'tile-jumbo.mp4'),
    ('boeuf', 'Smash Naan', 'p-smashnaan.jpg', 'Nouveau', 'gluten lait moutarde'),
    ('poulet', 'Le Tower', 'p-tower.jpg', '', 'gluten oeufs lait sesame moutarde', 'tile-tower.mp4'),
    ('poulet', 'Chicken Burger', 'p-chicken.jpg', '', 'gluten oeufs lait sesame'),
]
ALLERGENS = [('gluten','Gluten'),('lait','Lait'),('oeufs','Œufs'),('moutarde','Moutarde'),('sesame','Sésame'),('soja','Soja'),('celeri','Céleri'),('poissons','Poissons'),('crustaces','Crustacés'),('mollusques','Mollusques'),('arachides','Arachides'),('fruitsacoque','Fruits à coque'),('sulfites','Sulfites'),('lupin','Lupin')]

def prod(p, i):
    meat, name, img, badge, al = p[:5]
    video = p[5] if len(p) > 5 else ''
    b = f'<span class="badge">{badge}</span>' if badge else ''
    v = f'<video data-tile-video data-src="assets/video/{video}" muted loop playsinline preload="none" aria-hidden="true"></video>' if video else ''
    cls = 'product has-video' if video else 'product'
    return f'<a class="{cls}" href="produit.html" data-meat="{meat}" data-allergens="{al}" data-reveal style="--i:{i % 4}"><span class="product__name">{name}</span><div class="product__img">{b}<img src="assets/img/{img}" alt="{name}" width="600" height="600" loading="lazy">{v}</div></a>'

CATEG = f'''{tabs('Burgers')}
<main class="container">
  <div class="carte-head"><div><h1 id="titre">Nos burgers</h1><p>Bœuf ou poulet, simple ou double&#8239;: les légendes du burger, 100&#8239;% halal.</p></div></div>
</main>
<section aria-label="Offres burgers">{hs(CATEG_BANNERS, 'Offres burgers', 'hs--banner', 5000)}</section>
<main class="container">
  <div class="filters" id="allergenes">
    <div class="chips" role="group" aria-label="Filtrer par viande">
      <button class="chip" type="button" data-meat-filter="tous" aria-pressed="true">Tous</button>
      <button class="chip" type="button" data-meat-filter="boeuf" aria-pressed="false">Bœuf</button>
      <button class="chip" type="button" data-meat-filter="poulet" aria-pressed="false">Poulet</button>
    </div>
    <button class="chip" type="button" data-toggle-allergy aria-expanded="false" aria-controls="allergy">Filtrer par allergène</button>
  </div>
  <div class="allergy" id="allergy" data-open="false">
    <h2>Masquer les produits qui contiennent&#8239;:</h2>
    <div class="chips">{''.join(f'<button class="chip" type="button" data-allergen="{k}" aria-pressed="false">{v}</button>' for k, v in ALLERGENS)}</div>
    <small>Données d'exemple pour la maquette. Les traces éventuelles sont indiquées sur chaque fiche produit.</small>
  </div>
  <section class="group" data-group="boeuf" aria-labelledby="t-boeuf"><h2 id="t-boeuf">Bœuf</h2><div class="products">{''.join(prod(p, i) for i, p in enumerate(PRODUCTS[:4]))}</div></section>
  <section class="group" id="poulet" data-group="poulet" aria-labelledby="t-poulet"><h2 id="t-poulet">Poulet</h2><div class="products">{''.join(prod(p, i) for i, p in enumerate(PRODUCTS[4:]))}</div></section>
  <div class="empty" data-empty><h2 style="font-size:26px">Aucun burger ne correspond</h2><p style="margin:8px 0 16px;color:var(--muted)">Retirez un allergène ou changez de filtre.</p><button class="btn" type="button" data-reset>Réinitialiser les filtres</button></div>
</main>
<script>
(() => {{
  let meat = 'tous';
  const off = new Set();
  const cards = [...document.querySelectorAll('.product')];
  const groups = [...document.querySelectorAll('[data-group]')];
  const empty = document.querySelector('[data-empty]');
  const label = document.querySelector('[data-toggle-allergy]');
  const apply = () => {{
    cards.forEach((c) => {{
      const al = c.dataset.allergens.split(' ');
      c.hidden = (meat !== 'tous' && c.dataset.meat !== meat) || al.some((x) => off.has(x));
    }});
    let any = false;
    groups.forEach((g) => {{ const v = [...g.querySelectorAll('.product')].some((c) => !c.hidden); g.hidden = !v; any = any || v; }});
    empty.dataset.show = String(!any);
    label.textContent = off.size ? 'Allergènes (' + off.size + ')' : 'Filtrer par allergène';
    label.setAttribute('aria-pressed', String(off.size > 0));
  }};
  document.querySelectorAll('[data-meat-filter]').forEach((b) => b.addEventListener('click', () => {{
    meat = b.dataset.meatFilter;
    document.querySelectorAll('[data-meat-filter]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
    apply();
  }}));
  document.querySelectorAll('[data-allergen]').forEach((b) => b.addEventListener('click', () => {{
    const k = b.dataset.allergen; off.has(k) ? off.delete(k) : off.add(k);
    b.setAttribute('aria-pressed', String(off.has(k))); apply();
  }}));
  const panel = document.getElementById('allergy');
  label.addEventListener('click', () => {{ const o = panel.dataset.open !== 'true'; panel.dataset.open = String(o); label.setAttribute('aria-expanded', String(o)); }});
  document.querySelector('[data-reset]').addEventListener('click', () => {{
    meat = 'tous'; off.clear();
    document.querySelectorAll('[data-meat-filter]').forEach((x) => x.setAttribute('aria-pressed', String(x.dataset.meatFilter === 'tous')));
    document.querySelectorAll('[data-allergen]').forEach((x) => x.setAttribute('aria-pressed', 'false'));
    apply();
  }});
  if (location.hash === '#allergenes') {{ panel.dataset.open = 'true'; label.setAttribute('aria-expanded', 'true'); }}
}})();
</script>
'''

# ------------------------------------------------------------------ PRODUIT
PROD_CSS = '''
.p-band { background: var(--orange); }
.p-band__inner { position: relative; display: flex; justify-content: center; padding-block: 28px 0; }
.p-back { position: absolute; left: var(--pad); top: 24px; z-index: 1; display: inline-flex; align-items: center; gap: 6px; background: #fff; border-radius: 999px; padding: 8px 16px 8px 10px; font-family: var(--display); font-weight: 600; text-decoration: none; }
.p-back:hover { background: var(--tile); }
.p-band img { width: min(640px, 100%); aspect-ratio: 520 / 350; object-fit: cover; border-radius: 28px 28px 0 0; margin-top: 52px; }
.p-main { max-width: 860px; margin-inline: auto; padding: 40px var(--pad) 0; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 14px; }
.p-main h1 { font-size: clamp(40px, 5vw, 64px); }
.p-main > p { font-size: 19px; color: var(--muted); max-width: 52ch; }
.p-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; margin-top: 8px; }
.acc { max-width: 860px; margin: 40px auto 0; padding-inline: var(--pad); display: flex; flex-direction: column; gap: 12px; }
.acc details { background: #fff; border-radius: var(--radius); }
.acc summary { cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; font-family: var(--display); font-weight: 600; font-size: 22px; }
.acc summary::-webkit-details-marker { display: none; }
.acc summary svg { transition: transform .2s var(--ease); transform: rotate(90deg); }
.acc details[open] summary svg { transform: rotate(-90deg); }
.acc__body { padding: 0 24px 24px; display: flex; flex-direction: column; gap: 14px; }
.ingredients { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 24px; }
.ingredients li { display: flex; align-items: center; gap: 10px; }
.ingredients li::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--btn); flex: 0 0 auto; }
.al-list { display: flex; flex-wrap: wrap; gap: 8px; }
.al-list li { border-radius: 999px; padding: 6px 14px; font-weight: 600; background: var(--ink); color: #fff; }
.al-list.traces li { background: var(--tile); color: var(--ink); }
.acc h3 { font-size: 18px; }
.acc small { color: var(--muted); font-size: 14px; }
.acc table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.acc th, .acc td { padding: 10px 0; border-bottom: 1px solid rgba(43,20,6,.12); text-align: left; }
.acc td + td, .acc th + th { text-align: right; }
.with { max-width: 860px; margin: 56px auto 0; padding-inline: var(--pad); }
.with h2 { font-size: 30px; margin-bottom: 16px; }
.with__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
@media (max-width: 700px) { .ingredients { grid-template-columns: 1fr; } .with__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .p-back { top: 14px; } .p-band img { margin-top: 56px; } }
'''

PROD = f'''<main>
  <div class="p-band"><div class="container p-band__inner">
    <a class="p-back" href="categorie.html">{a('left')}Revenir aux burgers</a>
    <img src="assets/img/p-smashnaan.jpg" alt="Le Smash Naan : deux steaks smashés, cheddar fondu et cornichons dans un naan grillé" width="520" height="350">
  </div></div>
  <div class="p-main">
    <h1>Smash Naan</h1>
    <p>Deux steaks smashés sur la plaque, double cheddar fondu, cornichons et salade, le tout dans un naan grillé. [Description à valider]</p>
    <div class="p-actions"><a class="btn" href="index.html#appli">{a('bag')}Commander</a><a class="btn btn--outline" href="index.html#restaurants">Trouver un restaurant</a></div>
  </div>
  <div class="acc">
    <details open><summary>Ingrédients {a('right')}</summary><div class="acc__body">
      <ul class="ingredients"><li>Naan grillé</li><li>Steak de bœuf smashé ×2</li><li>Cheddar fondu ×2</li><li>Cornichons</li><li>Salade</li><li>Sauce maison</li></ul>
    </div></details>
    <details open><summary>Allergènes {a('right')}</summary><div class="acc__body">
      <h3>Contient</h3><ul class="al-list"><li>Gluten</li><li>Lait</li><li>Moutarde</li></ul>
      <h3>Peut contenir des traces de</h3><ul class="al-list traces"><li>Sésame</li><li>Œufs</li><li>Soja</li></ul>
      <small>Ces informations portent sur les 14 allergènes à déclaration obligatoire et viennent de nos fournisseurs. En cuisine, un contact accidentel avec d'autres allergènes ne peut pas être exclu. Données d'exemple pour la maquette.</small>
    </div></details>
    <details><summary>Valeurs nutritionnelles {a('right')}</summary><div class="acc__body">
      <table><thead><tr><th scope="col">Pour</th><th scope="col">1 portion</th><th scope="col">100&#8239;g</th></tr></thead><tbody>
      <tr><td>Énergie (kcal)</td><td>[—]</td><td>[—]</td></tr><tr><td>Matières grasses (g)</td><td>[—]</td><td>[—]</td></tr><tr><td>dont saturées (g)</td><td>[—]</td><td>[—]</td></tr><tr><td>Glucides (g)</td><td>[—]</td><td>[—]</td></tr><tr><td>dont sucres (g)</td><td>[—]</td><td>[—]</td></tr><tr><td>Protéines (g)</td><td>[—]</td><td>[—]</td></tr><tr><td>Sel (g)</td><td>[—]</td><td>[—]</td></tr>
      </tbody></table>
    </div></details>
  </div>
  <section class="with" aria-labelledby="t-with">
    <h2 id="t-with">Ça va bien avec</h2>
    <div class="with__grid">
      <a class="tile" href="carte.html#petite-faim"><div class="tile__img"><img src="assets/img/p-frites.jpg" alt="" width="600" height="600" loading="lazy"></div><span class="tile__label">Frites</span></a>
      <a class="tile" href="carte.html#petite-faim"><div class="tile__img"><img src="assets/img/p-nuggets.jpg" alt="" width="600" height="600" loading="lazy"></div><span class="tile__label">Nuggets</span></a>
      <a class="tile" href="carte.html#boissons"><div class="tile__img"><img src="assets/img/p-streetcola.jpg" alt="" width="600" height="600" loading="lazy"></div><span class="tile__label">Street Cola</span></a>
    </div>
  </section>
</main>
'''

PAGES = [
    dict(name='index.html', title='Big M burgers', desc="Big M, burgers 100 % halal depuis 2019 : actualités, carte, restaurants, appli, franchise.", css=HOME_CSS, body=HOME, current='home'),
    dict(name='carte.html', title='Notre carte · Big M', desc='La carte Big M : burgers, poulet, tex-mex, petite faim, desserts et boissons, avec les allergènes.', css=CARTE_CSS, body=CARTE, current='carte'),
    dict(name='categorie.html', title='Nos burgers · Big M', desc='Les burgers Big M, bœuf ou poulet, avec filtre par allergène.', css=CATEG_CSS, body=CATEG, current='carte'),
    dict(name='produit.html', title='Smash Naan · Big M', desc='Le Smash Naan : ingrédients, allergènes et valeurs nutritionnelles.', css=PROD_CSS, body=PROD, current='carte'),
]
