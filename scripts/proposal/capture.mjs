// Captures the screenshots for the client proposal (desktop slices + phone viewports).
// Slices follow the page's own sections so a screenshot never breaks mid-block.
// Usage: npm run proposal  (or node scripts/proposal/capture.mjs)
// Env: BASE_URL (default http://127.0.0.1:8102), ADMIN_EMAIL/ADMIN_PASSWORD, APPLICANT_EMAIL/APPLICANT_PASSWORD.
// Needs the demo data (php artisan migrate:fresh --seeder=DemoSeeder). Read-only: it only visits pages.
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8102';
const OUT = path.resolve('dist/proposal/shots');
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ADMIN = { email: process.env.ADMIN_EMAIL ?? 'admin@candid.test', password: process.env.ADMIN_PASSWORD ?? 'password' };
const APPLICANT = { email: process.env.APPLICANT_EMAIL ?? 'applicant@candid.test', password: process.env.APPLICANT_PASSWORD ?? 'password' };
const QUALITY = 80;

fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });

const PUBLIC = [
  ['home', '/'], ['services', '/services'], ['service-armoured', '/services/armored-transport'], ['about', '/about'],
  ['locations', '/locations'], ['careers', '/careers'], ['job', '/careers/security-officer-unarmed-freeport'], ['apply-1', '/apply'],
  ['quote', '/request-a-quote'], ['pickup', '/armoured-pickup'], ['contact', '/contact'],
];

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', protocolTimeout: 90000, args: ['--hide-scrollbars', '--no-sandbox', `--user-data-dir=${fs.mkdtempSync(path.join(path.resolve('dist/proposal'), 'chrome-'))}`] });
const page = await browser.newPage();
await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }, { name: 'prefers-color-scheme', value: 'light' }]);

async function settle() {
  await page.evaluate(async () => {
    document.querySelectorAll('[data-reveal]').forEach(e => e.classList.add('is-visible'));
    document.querySelectorAll('img[loading=lazy]').forEach(i => { i.loading = 'eager'; });
    // Back office on desktop: pin the fixed sidebar and sticky top bar to the document so every
    // slice shows them in place, and hide the "notification email not configured" notice that
    // only appears on an unconfigured dev server.
    [...document.querySelectorAll('main#content > p')].filter(p => p.textContent.includes('CANDID_NOTIFY_EMAIL')).forEach(p => p.remove());
    if (document.body.classList.contains('admin') && window.innerWidth >= 1024 && document.querySelector('main#content')) {
      const h = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
      const css = document.createElement('style');
      css.textContent = `body.admin > aside { position: absolute !important; bottom: auto !important; height: ${h}px !important; } body.admin header.sticky { position: relative !important; }`;
      document.head.append(css);
    }
    window.scrollTo(0, document.body.scrollHeight); await new Promise(r => setTimeout(r, 400)); window.scrollTo(0, 0);
    await Promise.race([
      Promise.all([...document.images].filter(i => !i.complete).map(i => new Promise(r => { i.onload = i.onerror = r; }))),
      new Promise(r => setTimeout(r, 2000)),
    ]);
  });
  await new Promise(r => setTimeout(r, 400));
}

/**
 * Measure natural content blocks (header, each section, footer). Blocks taller than the
 * page budget are replaced by their children, recursively, so slices follow real section
 * boundaries instead of cutting wherever the pixel budget runs out.
 */
async function measureBlocks(maxH) {
  return page.evaluate((maxH) => {
    const rect = (el) => { const r = el.getBoundingClientRect(); return { top: Math.round(r.top + window.scrollY), bottom: Math.round(r.bottom + window.scrollY) }; };
    const visible = (el) => { const cs = getComputedStyle(el); const r = el.getBoundingClientRect(); return cs.display !== 'none' && cs.visibility !== 'hidden' && r.height > 24 && cs.position !== 'fixed'; };
    const skip = (el) => el.matches('script, style, template, [x-cloak], [aria-hidden="true"]:not(section)');
    const label = (el) => {
      const hs = [...el.querySelectorAll('h1, h2, h3')].filter(h => visible(h) && h.closest('nav, footer, [aria-hidden="true"]') === null);
      const seen = new Set();
      const text = (h) => { const c = h.cloneNode(true); c.querySelectorAll('[aria-hidden="true"], template, .sr-only').forEach(e => e.remove()); return c.textContent.replace(/\s+/g, ' ').trim(); };
      return hs.map(text).filter(t => t && !seen.has(t.toLowerCase()) && seen.add(t.toLowerCase())).slice(0, 4);
    };
    const explode = (el) => {
      const r = { ...rect(el), labels: label(el) };
      if (r.bottom - r.top <= maxH) return [r];
      const kids = [...el.children].filter(k => !skip(k) && visible(k));
      if (kids.length === 0) return [{ top: r.top, bottom: r.bottom, labels: r.labels }];
      if (kids.length === 1) return explode(kids[0]); // wrapper div: look inside it
      return kids.flatMap(explode);
    };
    // Back office: the content column is <main id="content"> (top bar is a sticky header above it).
    // Public site and portal: body-level utility strip, header, <main id="main"> and footer.
    const adminMain = document.querySelector('body.admin main#content');
    const roots = adminMain
      ? [...adminMain.children].map(el => ({ el, trailing: false }))
      : [...document.body.children].filter(el => ['HEADER', 'MAIN', 'FOOTER', 'DIV', 'SECTION'].includes(el.tagName) && !skip(el) && visible(el))
          .flatMap(el => el.tagName === 'MAIN' ? [...el.children].map(k => ({ el: k, trailing: false })) : [{ el, trailing: el.tagName === 'FOOTER' }]);
    const blocks = roots.filter(r => visible(r.el)).flatMap(r => explode(r.el).map(b => ({ ...b, trailing: r.trailing }))).filter(b => b.bottom > b.top).sort((a, b) => a.top - b.top);
    const merged = [];
    for (const b of blocks) {
      const last = merged[merged.length - 1];
      if (last && b.top < last.bottom - 2) { last.bottom = Math.max(last.bottom, b.bottom); last.trailing = last.trailing && b.trailing; last.labels = [...new Set([...last.labels, ...b.labels])]; } else merged.push({ ...b, labels: [...b.labels] });
    }
    const docH = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
    if (merged.length) {
      merged[0].top = 0; // include the site header / back-office top bar
      merged[merged.length - 1].bottom = Math.max(merged[merged.length - 1].bottom, adminMain ? merged[merged.length - 1].bottom : docH);
      for (let i = 1; i < merged.length; i++) merged[i].top = merged[i - 1].bottom;
    }
    return { blocks: merged, docH };
  }, maxH);
}

/** Greedily pack consecutive blocks into page-sized groups; a lone oversize block is hard-cut as a last resort. */
function packBlocks(blocks, maxH, docH) {
  const groups = [];
  let cur = null;
  for (const b of blocks) {
    const h = b.bottom - b.top;
    if (cur && b.bottom - cur.top <= maxH) { cur.bottom = b.bottom; cur.labels = [...new Set([...cur.labels, ...(b.labels ?? [])])]; continue; }
    if (cur) groups.push(cur);
    if (h > maxH) {
      for (let y = b.top; y < b.bottom; y += maxH) groups.push({ top: y, bottom: Math.min(y + maxH, b.bottom), labels: b.labels ?? [] });
      cur = null;
    } else cur = { top: b.top, bottom: b.bottom, labels: [...(b.labels ?? [])] };
  }
  if (cur) groups.push(cur);
  // swallow a tiny trailing sliver into the previous group when it fits
  if (groups.length > 1) {
    const last = groups[groups.length - 1], prev = groups[groups.length - 2];
    if (last.bottom - last.top < maxH * 0.15 && last.bottom - prev.top <= maxH * 1.05) { prev.bottom = last.bottom; prev.labels = [...new Set([...prev.labels, ...last.labels])]; groups.pop(); }
  }
  groups[groups.length - 1].bottom = Math.min(groups[groups.length - 1].bottom, docH);
  return groups;
}

const RATIO = 1.08; // slice height ÷ width that fits a letter page beneath the heading and bullets

async function shoot(name, url, { width = 1440, height = 900, fullPage = true, mobile = false } = {}) {
  await page.setViewport({ width, height, deviceScaleFactor: mobile ? 2 : 1.25, isMobile: mobile, hasTouch: mobile });
  const res = await page.goto(BASE + url, { waitUntil: 'networkidle0' });
  if (!res || res.status() >= 400) throw new Error(`${url} returned ${res?.status()}`);
  await settle();
  const files = []; const labels = [];
  if (!fullPage) {
    const file = path.join(OUT, `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: QUALITY });
    files.push(file);
    console.log(`✓ ${name} viewport`);
    return { name, url, chunks: files.map(f => path.resolve(f)), labels: [[]], mobile };
  }
  const maxH = Math.round(width * RATIO);
  let { blocks, docH } = await measureBlocks(maxH);
  if (name !== 'home') { blocks = blocks.filter(b => !b.trailing); docH = blocks[blocks.length - 1].bottom; } // footer is shown once (home)
  const groups = packBlocks(blocks, maxH, docH);
  for (const [i, g] of groups.entries()) {
    const file = path.join(OUT, groups.length > 1 ? `${name}-${i}.jpg` : `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: QUALITY, captureBeyondViewport: true, clip: { x: 0, y: g.top, width, height: g.bottom - g.top } });
    files.push(file); labels.push(g.labels ?? []);
  }
  console.log(`✓ ${name} → ${groups.length} slice(s): ${groups.map(g => (g.labels ?? []).join(' | ') || '(no heading)').join('  ·  ')}`);
  return { name, url, chunks: files.map(f => path.resolve(f)), labels, w: width, h: docH };
}

async function login(url, email, password) {
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto(BASE + url, { waitUntil: 'networkidle0' });
  const form = `form[action$="${url}"]`;
  // Set the fields directly: typed keystrokes were sometimes lost on the staff form after visiting the portal profile.
  await page.$eval(form, (f, e, pw) => { f.querySelector('[name=email]').value = e; f.querySelector('[name=password]').value = pw; }, email, password);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load', timeout: 60000 }), page.$eval(form, (f) => f.requestSubmit())]);
  if (new URL(page.url()).pathname === url) throw new Error(`Sign-in failed at ${url} for ${email}`);
}

/** First link on the current page whose path matches the pattern (the demo references are random per seed). */
async function findLink(pattern) {
  const href = await page.evaluate((src) => [...document.querySelectorAll('a[href]')].map(a => new URL(a.href).pathname).find(p => new RegExp(src).test(p)), pattern.source);
  if (!href) throw new Error(`No link matching ${pattern} on ${page.url()}`);
  return href;
}

const shots = [];
for (const [n, u] of PUBLIC) shots.push(await shoot(n, u));
shots.push(await shoot('portal-login', '/portal/login'));
shots.push(await shoot('staff-login', '/login'));

// Applicant portal: the demo applicant has one application at the entrance-exam stage and one draft saved up to step 5.
await login('/portal/login', APPLICANT.email, APPLICANT.password);
await page.goto(BASE + '/portal', { waitUntil: 'networkidle0' });
const appUrl = await findLink(/^\/portal\/applications\/CSL-/);
const draftRef = (await findLink(/^\/apply\/CSL-[A-Z0-9]+\/step\/\d$/)).split('/')[2];
shots.push(await shoot('portal-dashboard', '/portal'));
shots.push(await shoot('portal-application', appUrl));
for (const s of [2, 3, 4, 5]) shots.push(await shoot(`apply-${s}`, `/apply/${draftRef}/step/${s}`));
shots.push(await shoot('portal-inbox', '/portal/inbox'));
shots.push(await shoot('portal-profile', '/portal/profile'));

// Back office, signed in as the demo administrator (sees recruitment and operations).
await login('/login', ADMIN.email, ADMIN.password);
await page.goto(BASE + '/admin/applications?q=Jordan', { waitUntil: 'networkidle0' });
const applicantUrl = await findLink(/^\/admin\/applications\/\d+$/);
await page.goto(BASE + '/admin/requests?kind=quote', { waitUntil: 'networkidle0' });
const requestUrl = await findLink(/^\/admin\/requests\/\d+$/);
const ADMIN_PAGES = [
  ['admin-dashboard', '/admin'], ['admin-pipeline', '/admin/applications/pipeline', false], ['admin-applicants', '/admin/applications'],
  ['admin-applicant', applicantUrl], ['admin-slots', '/admin/slots'], ['admin-requests', '/admin/requests?kind=quote', false],
  ['admin-request', requestUrl], ['admin-jobs', '/admin/jobs'], ['admin-templates', '/admin/templates'],
  ['admin-settings', '/admin/settings'], ['admin-users', '/admin/users'],
];
for (const [n, u, full = true] of ADMIN_PAGES) shots.push(await shoot(n, u, { fullPage: full }));

// Phones last: switching to mobile emulation reloads the page, which would race a sign-in form.
// Staff and applicant sessions live side by side, so the portal page still works here.
const MOBILE = [['m-home', '/'], ['m-careers', '/careers'], ['m-apply', '/apply'], ['m-portal-application', appUrl], ['m-admin-dashboard', '/admin'], ['m-admin-pipeline', '/admin/applications/pipeline']];
for (const [n, u] of MOBILE) shots.push(await shoot(n, u, { width: 390, height: 844, mobile: true, fullPage: false }));

fs.writeFileSync(path.join(OUT, 'chunks.json'), JSON.stringify(Object.fromEntries(shots.map(s => [s.name, s])), null, 1));
await browser.close();
for (const d of fs.readdirSync(path.resolve('dist/proposal'))) if (d.startsWith('chrome-')) fs.rmSync(path.resolve('dist/proposal', d), { recursive: true, force: true });
console.log('done');
