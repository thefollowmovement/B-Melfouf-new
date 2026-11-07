# Assemble les pages du prototype Big M v3 (en-tête et pied de page communs).
import pathlib

ROOT = pathlib.Path(__file__).parent

ICON = {
    'pin': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
    'bag': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>',
    'menu': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
    'close': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    'left': '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>',
    'right': '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>',
    'arrow': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>',
    'apple': '<svg viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M16.4 12.6c0-2.4 2-3.5 2-3.6-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.1-2.8.9-3.5.9s-1.8-.9-3-.8C7 7.3 5.6 8.2 4.8 9.6c-1.6 2.8-.4 6.9 1.1 9.2.8 1.1 1.7 2.3 2.9 2.3s1.6-.7 3-.7 1.8.7 3 .7c1.3 0 2.1-1.1 2.8-2.3.9-1.3 1.3-2.5 1.3-2.6 0 0-2.5-1-2.5-3.6zM14.1 5.6c.6-.8 1.1-1.8 1-2.9-.9 0-2.1.6-2.7 1.4-.6.7-1.1 1.8-1 2.8 1 .1 2.1-.5 2.7-1.3z"/></svg>',
    'play': '<svg viewBox="0 0 24 26" aria-hidden="true"><path d="M3 2l11 11L3 24c-.6-.3-1-.9-1-1.6V3.6C2 2.9 2.4 2.3 3 2z" fill="#34A853"/><path d="M14 13l3.5-3.5 3.6 2c1.2.7 1.2 2.3 0 3l-3.6 2L14 13z" fill="#FBBC04"/><path d="M3 2c.4-.2 1-.2 1.5.1l13 7.4L14 13 3 2z" fill="#4285F4"/><path d="M3 24l11-11 3.5 3.5-13 7.4c-.5.3-1.1.3-1.5.1z" fill="#EA4335"/></svg>',
    'insta': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor"/></svg>',
    'x': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 4l16 16M20 4L4 20"/></svg>',
    'snap': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" aria-hidden="true"><path d="M12 3c3 0 5 2.2 5 5.2v2.3l1.8.6-.9 1.6 1.6 1.2c-1.4.9-3 .9-3.7 1.9-.5.8-1.3 1.7-3.8 1.7s-3.3-.9-3.8-1.7c-.7-1-2.3-1-3.7-1.9l1.6-1.2-.9-1.6 1.8-.6V8.2C7 5.2 9 3 12 3z"/></svg>',
    'tiktok': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.4 2.6 2.2 4.4 5 4.6"/></svg>',
}

STORES = f'''<div class="stores">
  <a class="store" href="https://apps.apple.com/fr/app/big-m/id6444707190">{ICON['apple']}<span><small>Télécharger dans</small><b>l'App Store</b></span></a>
  <a class="store" href="https://play.google.com/store/apps/details?id=com.dishop.big&amp;gl=FR">{ICON['play']}<span><small>Disponible sur</small><b>Google Play</b></span></a>
</div>'''

def head(title, desc, extra_css):
    return f'''<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{title}</title>
<meta name="description" content="{desc}">
<script>document.documentElement.classList.add('js')</script>
<link rel="preload" href="assets/fonts/fredoka-latin-600-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/site.css">
<style>
{extra_css}
</style>
</head>
<body>
'''

def header(current):
    def cur(name):
        return ' aria-current="page"' if name == current else ''
    return f'''<header class="header">
  <div class="header__inner">
    <div class="header__left">
      <button class="burger" type="button" data-open-drawer aria-expanded="false" aria-controls="drawer" aria-label="Ouvrir le menu">{ICON['menu']}</button>
      <nav class="header__nav" aria-label="Navigation principale">
        <a href="carte.html"{cur('carte')}>Notre carte</a>
        <a href="index.html#actus"{cur('actus')}>Actualités</a>
        <a href="index.html#franchise"{cur('franchise')}>Franchise</a>
        <a href="index.html#recrutement"{cur('recrutement')}>Recrutez-nous&#8239;!</a>
      </nav>
    </div>
    <a class="header__logo" href="index.html" aria-label="Big M, accueil"><img src="assets/img/logo.webp" alt="Big M" width="273" height="352"></a>
    <div class="header__right">
      <a class="hide-sm" href="index.html#restaurants">{ICON['pin']}Restaurants</a>
      <a class="btn" href="index.html#appli">{ICON['bag']}Commander</a>
    </div>
  </div>
</header>
<div class="drawer" id="drawer" data-open="false" role="dialog" aria-modal="true" aria-label="Menu">
  <div class="drawer__scrim" data-close-drawer></div>
  <nav class="drawer__panel" aria-label="Menu">
    <button class="drawer__close" type="button" data-close-drawer aria-label="Fermer le menu">{ICON['close']}</button>
    <a href="index.html">Accueil</a>
    <a href="carte.html">Notre carte</a>
    <a href="index.html#restaurants">Nos restaurants</a>
    <a href="index.html#actus">Actualités</a>
    <a href="index.html#franchise">Devenir franchisé</a>
    <a href="index.html#recrutement">Recrutez-nous&#8239;!</a>
    <a href="index.html#legendes">Jeu concours</a>
  </nav>
</div>
'''

FOOTER = f'''<footer class="footer">
  <div class="container footer__top">
    <div class="footer__brand">
      <img src="assets/img/logo.webp" alt="Big M" width="273" height="352" loading="lazy">
      <div class="footer__social">
        <a href="https://www.instagram.com/bigm_officiel/" aria-label="Instagram">{ICON['insta']}</a>
        <a href="https://www.snapchat.com/add/bigm.officiel" aria-label="Snapchat">{ICON['snap']}</a>
        <a href="https://twitter.com/BigM_officiel" aria-label="X">{ICON['x']}</a>
      </div>
      <p style="font-family: var(--display); font-weight: 600; font-size: 18px; color: #fff">Big M dans votre poche</p>
      {STORES}
    </div>
    <div>
      <h3>Produits</h3>
      <ul>
        <li><a href="carte.html">Notre carte</a></li>
        <li><a href="categorie.html">Nos burgers</a></li>
        <li><a href="carte.html">Nos menus</a></li>
        <li><a href="categorie.html#allergenes">Allergènes</a></li>
      </ul>
    </div>
    <div>
      <h3>L'entreprise</h3>
      <ul>
        <li><a href="index.html#legendes">Notre histoire</a></li>
        <li><a href="index.html#actus">Actualités et presse</a></li>
        <li><a href="index.html#franchise">Devenir franchisé</a></li>
        <li><a href="index.html#recrutement">Recrutement</a></li>
        <li><a href="index.html#legendes">Jeu concours</a></li>
      </ul>
    </div>
    <div>
      <h3>Contact</h3>
      <ul>
        <li><a href="mailto:contact@bigm-burger.fr">contact@bigm-burger.fr</a></li>
        <li>Siège&#8239;: Villeneuve-la-Garenne</li>
        <li><a href="#">Mentions légales</a></li>
        <li><a href="#">Politique de confidentialité</a></li>
        <li><a href="#">Gestion des cookies</a></li>
      </ul>
    </div>
  </div>
  <div class="container footer__bottom">
    <span>© 2026 Big M, c'est ça qu'on aime&#8239;!</span>
    <span>Pour votre santé, mangez au moins cinq fruits et légumes par jour. www.mangerbouger.fr</span>
  </div>
</footer>
<script src="assets/site.js"></script>
'''

CATS = [
    ('Nouveautés', 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z'),
    ('Menus', 'M5 8h14l-1.2 12H6.2L5 8z M9 8V6a3 3 0 0 1 6 0v2'),
    ('Burgers', 'M4 10a8 6 0 0 1 16 0z M3 13.5h18 M4.5 17h15v.5a2.5 2.5 0 0 1-2.5 2.5H7a2.5 2.5 0 0 1-2.5-2.5z'),
    ('Poulet', 'M15 3.5a5.5 5.5 0 0 1 3.9 9.4l-2.4 2.4-4.6-4.6.2-.3A5.5 5.5 0 0 1 15 3.5z M11.9 10.7 6.5 16.1 M5 15a2 2 0 1 0 2.5 2.5 2 2 0 1 0 1.5-2.6'),
    ('Tex-Mex', 'M12 3c1 3 5 4.5 5 9.5a5 5 0 0 1-10 0c0-2.3 1.2-3.7 2.4-4.8 0 2 .9 3.3 2.1 3.3 0-3.3-.9-5.4.5-8z'),
    ('Petite faim', 'M6 10h12l-1.5 10h-9z M8 10V4 M11 10V3 M14 10V4 M16.5 10V5.5'),
    ('Desserts', 'M7.5 10a4.5 4.5 0 0 1 9 0z M7.5 10 12 21l4.5-11'),
    ('Boissons', 'M7 7h10l-1.2 13H8.2z M6 7h12 M12 7l2-4h3'),
]

def tabs(active):
    items = ['<li><a href="carte.html"%s><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 11l8-7 8 7v9h-5v-6H9v6H4z"/></svg>Toute la carte</a></li>' % (' aria-current="page"' if active == 'all' else '')]
    for name, d in CATS:
        href = 'categorie.html' if name == 'Burgers' else 'carte.html#' + name.lower().replace(' ', '-').replace('é', 'e')
        a = ' aria-current="page"' if name == active else ''
        items.append(f'<li><a href="{href}"{a}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{d}"/></svg>{name}</a></li>')
    return '<nav class="cat-tabs" aria-label="Catégories de la carte"><ul class="container">' + ''.join(items) + '</ul></nav>'

TABS_CSS = '''
.cat-tabs { position: sticky; top: 88px; z-index: 40; max-width: 1240px; margin: 10px auto 0; width: calc(100% - 2 * var(--pad)); border-radius: 20px; background: rgba(255, 255, 255, .9); -webkit-backdrop-filter: blur(22px) saturate(180%); backdrop-filter: blur(22px) saturate(180%); box-shadow: 0 0 0 1px rgba(43, 20, 6, .06), 0 10px 28px -16px rgba(43, 20, 6, .25); overflow: hidden; }
@media (prefers-reduced-transparency: reduce) { .cat-tabs { background: #fff; -webkit-backdrop-filter: none; backdrop-filter: none; } }
.cat-tabs ul { display: flex; gap: 4px; overflow-x: auto; scrollbar-width: none; justify-content: space-between; padding-inline: 8px; max-width: none; }
.cat-tabs ul::-webkit-scrollbar { display: none; }
.cat-tabs a { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 12px 10px; min-width: 88px; text-decoration: none; font-family: var(--display); font-weight: 600; font-size: 15px; white-space: nowrap; color: var(--ink); border-bottom: 3px solid transparent; transition: color .18s ease, border-color .18s ease; }
.cat-tabs a:hover { color: var(--btn); }
.cat-tabs a[aria-current="page"] { color: var(--btn); border-color: var(--btn); }
.cat-tabs svg { width: 28px; height: 28px; }
@media (max-width: 960px) { .cat-tabs ul { justify-content: flex-start; } }
@media (max-width: 760px) { .cat-tabs { top: 76px; width: calc(100% - 20px); border-radius: 18px; } .cat-tabs a { min-width: 76px; padding: 8px 10px 10px; font-size: 14px; } .cat-tabs svg { width: 24px; height: 24px; } }
'''

def tabbar(current):
    def cur(n):
        return ' aria-current="page"' if n == current else ''
    ic = lambda d: f'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="tabbar__ico" d="{d}"/></svg>'
    return f'''<nav class="tabbar" aria-label="Navigation rapide">
  <div class="tabbar__bar">
    <a class="tabbar__item" href="index.html"{cur('home')}>{ic('M4 11l8-7 8 7v9h-5v-6H9v6H4z')}Accueil</a>
    <a class="tabbar__item" href="carte.html"{cur('carte')}>{ic('M4 10a8 6 0 0 1 16 0z M3 13.5h18 M4.5 17h15v.5a2.5 2.5 0 0 1-2.5 2.5H7a2.5 2.5 0 0 1-2.5-2.5z')}La carte</a>
    <a class="tabbar__fab" href="index.html#appli" aria-label="Commander"><img src="assets/img/logo.webp" alt="" width="273" height="352"></a>
    <a class="tabbar__item" href="index.html#restaurants"{cur('restos')}>{ic('M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z M12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z')}Restos</a>
    <button class="tabbar__item" type="button" data-open-drawer aria-controls="drawer" aria-expanded="false">{ic('M4 7h16M4 12h16M4 17h16')}Plus</button>
    <span class="tabbar__label" aria-hidden="true">Commander</span>
  </div>
</nav>
'''

def build(name, title, desc, css, body, current):
    src = head(title, desc, css) + header(current) + body + tabbar(current) + FOOTER + '</body>\n</html>\n'
    (ROOT / name).write_text(src, encoding='utf-8')

if __name__ == '__main__':
    import pages
    for p in pages.PAGES:
        build(**p)
    print('ok')
