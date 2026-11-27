// Parcours du bandeau de cookies dans Chromium (Playwright) : première visite, refus, rechargement,
// lien « Paramétrer les cookies », accord, retrait. SITE=http://… node tests/navigateur/test-cookies.js
const { chromium, devices } = require('playwright');
// Site de test avec bigm-cookies actif et un faux Google Analytics (identifiant google_gtagjs) qui pose
// window.__gaCharge et un cookie _ga : voir docs/cookies.md.
const URL = process.env.SITE || 'http://127.0.0.1:8088/';
// Captures d'écran hors du dépôt : dossier CAPTURES, sinon le dossier temporaire du système.
const path = require('path');
const CAPTURES = process.env.CAPTURES || require('os').tmpdir();
const capture = (nom) => path.join(CAPTURES, nom);
(async () => {
  const b = await chromium.launch();
  const ctx = await b.newContext({ viewport: { width: 1366, height: 900 } });
  const p = await ctx.newPage();
  const reqs = []; p.on('request', (r) => { if (r.url().includes('/faux-ga/')) reqs.push(r.url().split('/').pop()); });
  const etat = async () => p.evaluate(() => ({ bandeau: !document.getElementById('bigm-cookies').hidden, ga: !!window.__gaCharge, config: !!window.__gaConfig, events: !!window.__gaEvents, ga_cookie: /(^|; )_ga=/.test(document.cookie), choix: (document.cookie.match(/bigm_cookies=([^;]*)/) || [])[1] ? decodeURIComponent(document.cookie.match(/bigm_cookies=([^;]*)/)[1]) : null, dl: JSON.stringify((window.dataLayer || []).filter(x => x[0] === 'consent').map(x => [x[1], x[2].analytics_storage])) }));
  await p.goto(URL, { waitUntil: 'networkidle' });
  console.log('1. première visite :', JSON.stringify(await etat()), 'requêtes GA:', reqs.length);
  const pos = await p.evaluate(() => [...document.body.querySelectorAll('*')].indexOf(document.getElementById('bigm-cookies')));
  console.log('   position dans la page (0 = premier élément) :', pos);
  await p.screenshot({ path: capture('cookies-ordi.png') });
  await p.keyboard.press('Tab'); console.log('   1re tabulation :', await p.evaluate(() => document.activeElement.textContent.trim().slice(0, 30)));
  await p.click('[data-bigm-cookies-action="refuser"]'); await p.waitForTimeout(500);
  console.log('2. Tout refuser :', JSON.stringify(await etat()), 'requêtes GA:', reqs.length);
  await p.reload({ waitUntil: 'networkidle' });
  console.log('3. après rechargement :', JSON.stringify(await etat()), 'requêtes GA:', reqs.length);
  await p.click('a[href$="#gestion-cookies"]'); await p.waitForTimeout(300);
  console.log('4. lien Gestion des cookies :', JSON.stringify(await etat()), 'focus:', await p.evaluate(() => document.activeElement.id), 'panneau:', await p.evaluate(() => !document.getElementById('bigm-cookies-choix').hidden));
  await p.screenshot({ path: capture('cookies-panneau.png') });
  await p.check('#bigm-cookies-mesure'); await p.click('[data-bigm-cookies-action="enregistrer"]'); await p.waitForTimeout(1200);
  console.log('5. mesure cochée + Enregistrer :', JSON.stringify(await etat()), 'requêtes GA:', reqs.join(','));
  await p.reload({ waitUntil: 'networkidle' }); await p.waitForTimeout(800);
  console.log('6. rechargement avec accord :', JSON.stringify(await etat()));
  await p.click('a[href$="#gestion-cookies"]'); await p.click('[data-bigm-cookies-action="refuser"]'); await p.waitForLoadState('networkidle'); await p.waitForTimeout(800);
  console.log('7. retrait de l’accord :', JSON.stringify(await etat()));
  const m = await b.newContext({ ...devices['iPhone 13'] }); const pm = await m.newPage(); await pm.goto(URL, { waitUntil: 'networkidle' }); await pm.screenshot({ path: capture('cookies-mobile.png') });
  await b.close();
})();
