// Builds the client proposal (letter-size PDF) from the captured screenshots, in Candid's own palette and fonts.
// Usage: npm run proposal  (capture first, then this). Output: dist/proposal/Candid-Security-Website-Proposal.pdf
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT = path.resolve('.');
const SHOTS = path.resolve('dist/proposal/shots');
const chunks = JSON.parse(fs.readFileSync(path.join(SHOTS, 'chunks.json'), 'utf8'));
const OUT_HTML = path.resolve('dist/proposal/proposal.html');
const OUT_PDF = path.resolve('dist/proposal/Candid-Security-Website-Proposal.pdf');
const DATE = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const TESTS = process.env.TEST_COUNT ?? '76';
const file = (f) => 'file://' + f;

// Chrome embeds WebP backgrounds losslessly (several MB), so hand it a JPEG of the cover photo (macOS sips).
let COVER = path.join(ROOT, 'public/img/nassau-1920.webp');
try {
  const jpg = path.join(SHOTS, 'cover.jpg');
  execFileSync('sips', ['-Z', '1600', '-s', 'format', 'jpeg', '-s', 'formatOptions', '78', COVER, '--out', jpg], { stdio: 'ignore' });
  COVER = jpg;
} catch { /* not on macOS: keep the WebP */ }
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

// Self-hosted site fonts (Archivo variable from resources/fonts, Work Sans + JetBrains Mono from the Vite build).
const asset = (prefix) => {
  const f = fs.readdirSync(path.join(ROOT, 'public/build/assets')).find(n => n.startsWith(prefix) && n.endsWith('.woff2'));
  if (!f) throw new Error(`Font ${prefix} not found in public/build/assets; run npm run build`);
  return file(path.join(ROOT, 'public/build/assets', f));
};
const FONTS = `
  @font-face { font-family: Archivo; src: url("${file(path.join(ROOT, 'resources/fonts/archivo-variable-latin.woff2'))}") format("woff2"); font-weight: 100 900; font-stretch: 62% 125%; }
  @font-face { font-family: "Work Sans"; src: url("${asset('work-sans-400-normal')}") format("woff2"); font-weight: 400; }
  @font-face { font-family: "Work Sans"; src: url("${asset('work-sans-500-normal')}") format("woff2"); font-weight: 500; }
  @font-face { font-family: "Work Sans"; src: url("${asset('work-sans-600-normal')}") format("woff2"); font-weight: 600; }
  @font-face { font-family: "Work Sans"; src: url("${asset('work-sans-700-normal')}") format("woff2"); font-weight: 700; }
  @font-face { font-family: "JetBrains Mono"; src: url("${asset('jetbrains-mono-500-normal')}") format("woff2"); font-weight: 500; }`;

// Candid's own round badge, cut from the candidbahamas.com header (the largest copy online, 143 px).
const BADGE = file(path.join(ROOT, 'public/img/brand/candid-badge.png'));
const MARK = (size = 44) => `<img src="${BADGE}" width="${size}" height="${size}" alt="" style="flex:none">`;
const WORDMARK = (tone = 'light') => `<div class="brand ${tone}">${MARK(60)}<div><span class="n">Candid</span><span class="s">Security · Bahamas</span></div></div>`;
const ROUTE = '<div class="route" aria-hidden="true"><span class="checkpoint"></span><span class="dots"></span></div>';

/** Hand-written captions per slice, from what is actually on each one. */
const CAPTIONS = {
  home: [
    'Hero: “Security officers on post across The Bahamas.” Request-a-quote and apply buttons, and a call-an-office card with both numbers and the online armoured pickup link.',
    '“Five services, one Bahamian company.” A photo card for each service with the year it started; Candid’s own crew photos are used where they exist.',
    'Where Candid works: the Freeport head office, the Nassau office and client sites in Abaco and Bimini, along the patrol-route line used across the site.',
    '“Built up one service at a time”: the 1997–2016 timeline from the company history page and The Tribune (August 2016).',
    'Careers band: “Apply online. No more paper forms.” with the hiring checkpoints and the latest openings (demo postings), then the quote and armoured pickup prompts.',
    'Footer: every service, company links, both offices with tap-to-call numbers, and the general email address.',
  ],
  services: [
    'Services hero over Nassau Harbour, then the first service cards (security officers, armoured transport), each with what is included and two actions.',
    'Mobile patrol, aviation security and lifeguards, in the same format.',
  ],
  'service-armoured': [
    'A service page: hero photograph and breadcrumb.',
    'Description, what is included, Candid’s own armoured-crew photo, who it is for and where; the side card books a pickup or calls either office.',
    'Links to the other four services.',
  ],
  about: [
    'Hero and company history: founded in Freeport in 1997 by Cleveland A. and Marcia Duncombe, with the timeline beside the text.',
    'Executive and management team as listed on the current site (titles to be confirmed with Candid before launch).',
    'Mission statement quoted from candidbahamas.com, the four core values, the training approach with Candid’s own classroom photo, and the call to action.',
  ],
  locations: [
    'Both offices with address, phone, fax and hours, plus call and map buttons. Hours read “please call” until Candid supplies them.',
    'Abaco and Bimini coverage, with a request-cover button and a live count of open positions on each island.',
  ],
  careers: [
    'Careers hero and the six hiring checkpoints, from applying online to background check and offer.',
    'Open positions with island filters; each card shows island, hours and number of openings (demo postings).',
    'Requirements and benefits from the current careers page, and the document checklist. No National Insurance number is collected online.',
  ],
  job: ['A job posting: duties, requirements and documents to upload, with the Apply online card and other openings on the same island (demo posting). Job pages carry Google for Jobs data.'],
  'apply-1': [
    'Step 1 header: the reference badge and the step count.',
    'Step 1, “About you”: personal details, right to work and emergency contact. A password at the end creates the applicant account, so the form can be finished later.',
  ],
  quote: [
    'Guard-service quote, part 1: service, type of site and address, beside both office cards.',
    'Part 2, the cover: armed or unarmed, number of posts, days per week, hours, contract length and start date.',
    'Part 3, contact details. The request lands on the operations board with a reference number.',
  ],
  pickup: [
    'Armoured pickup request hero.',
    'The pickup: safety note (no cash amounts online), pickup and delivery addresses, date, time window, frequency and whether the business is already a client. The business contact details follow.',
    'Business contact details and send. Operations confirms the window by phone.',
  ],
  contact: [
    'Contact hero.',
    'Quick routes (officers for a site, armoured pickup, jobs), the message form, both office cards and the two published email addresses.',
  ],
  'portal-login': ['Applicant sign-in, separate from staff accounts, with password reset and a link to start an application.'],
  'staff-login': ['Staff sign-in for the back office.'],
  'portal-dashboard': ['Applicant dashboard (demo applicant): a draft to continue, a sent application flagged “Action needed” at the entrance-exam stage, recent messages and other openings.'],
  'portal-application': [
    'Application header with the portal menu.',
    'Status tracker across the hiring stages, “Book your entrance exam” with sittings on the applicant’s island and seats left, documents with their review status and an upload form, and the timeline.',
  ],
  'apply-2': [
    'Steps 2–5 for a signed-in applicant: the opening, island and step are always visible.',
    'Step 2, “Position & availability”: opening, type of work, islands, hours, shifts and earliest start date, with Back and Save & finish later.',
  ],
  'apply-3': [null, 'Step 3, “Licences & experience”: driver’s licence, security and firearm licences, years of experience, last employer, education and certificates.'],
  'apply-4': [null, 'Step 4, “Documents”: six upload slots (police certificate and ID needed before the document check). Phone photos, PDF or Word, up to 8 MB each; files already uploaded are listed.'],
  'apply-5': [null, 'Step 5, “Review & send”: every answer in one table, the exam pass mark, and the declaration and consent tick boxes.'],
  'portal-inbox': ['Messages: every update the recruitment team sends is kept here as well as emailed.'],
  'portal-profile': ['Profile: name, phone and password. The email address is changed through the recruitment office.'],
  'admin-dashboard': [
    '“Today” leads with the number to act on: applicants waiting on the document check and client requests unanswered for over 24 hours. Below: pipeline tiles, applicants by stage, applications per week, latest applications and the next screening sessions.',
    'Operations: open quotes, pickups and messages, pickups due in the next 48 hours, and the latest client requests with overdue flags.',
  ],
  'admin-pipeline': ['Hiring pipeline: drag cards between stages (New, Document check, Entrance exam, Drug test and on to Offer). Each card shows island, missing documents, exam score, booking status, recruiter and time in stage, plus a move menu.'],
  'admin-applicants': [
    'Applicant list: stage tabs with counts, and filters for island, stage, position, posting, recruiter and missing documents.',
    'Each row: reference, position, island, stage, document status, exam score (below the pass mark shows in red), recruiter and when sent. Rows can be selected for bulk email. (Demo applicants.)',
    'Bulk email to the selected applicants from a template, optionally moving them to the next stage.',
  ],
  'admin-applicant': [
    'Applicant page: stage tracker, “Move & notify” with an internal comment, documents to accept or send back with a reason, screening results (exam, drug test, background check), recruiter and rating, bookings and the portal account.',
    'The full application, an email composer with templates and placeholders, and the notes and timeline.',
  ],
  'admin-slots': ['Screening sessions: add exam sittings, drug-test sessions or interview blocks (with weekly repeats); applicants book the seats themselves from the portal.'],
  'admin-requests': ['Operations board for guard-service quotes: New, Contacted, Site visit, Quote sent and on to Won or Lost. Requests waiting over 24 hours are flagged. Pickups and messages have their own boards. (Sample requests.)'],
  'admin-request': ['A quote request: site type, posts, armed or unarmed, hours, days and duration, with status, owner and internal notes.'],
  'admin-jobs': ['Job postings by island, with status, openings and active applicants. Closing dates take a posting off the site automatically.'],
  'admin-templates': ['Email templates for each stage (documents missing, exam, drug test, interview, offer, not successful) and the stage each one moves the applicant to.'],
  'admin-settings': ['Office settings: phones, faxes, addresses, office hours, emails, the exam pass mark and a careers notice. Changes show on the website straight away.'],
  'admin-users': ['Staff accounts with three roles: administrator, recruiter and operations.'],
};

/** Screens per part: [key, title, bullets, options]. */
const SECTIONS = [
  { id: 'public', eyebrow: 'Part 1', title: 'The public website', intro: 'A mobile-first replacement for candidbahamas.com. The copy comes from the current site and The Tribune (August 2016), rewritten for clarity. Photos are Candid’s own where they exist and licensed Bahamian photography elsewhere.', screens: [
    ['home', 'Home page', ['Leads with the five services and both office numbers, one tap away on a phone.', 'Company history, the islands covered and current openings, all on one page.']],
    ['services', 'Services', ['All five services on one page, each with what is included and a direct quote or pickup request.']],
    ['service-armoured', 'Service page', ['Every service has its own page with description, inclusions, who it is for, where it runs and a booking card.'], { skip: [2] }],
    ['about', 'About Candid', ['History, leadership, mission and core values: the credibility page for tenders and corporate clients.']],
    ['locations', 'Locations', ['Freeport head office and Nassau office with tap-to-call and map links; Abaco and Bimini coverage.']],
    ['careers', 'Careers', ['Explains the hiring process up front, lists openings by island and tells applicants which documents to have ready.']],
    ['job', 'Job posting', ['Duties, requirements and documents per opening, with Google for Jobs data so openings can appear in job searches.']],
    ['quote', 'Guard-service quote', ['A structured request (site, cover, posts, hours, contract length) instead of a phone call or a generic email.']],
    ['pickup', 'Armoured pickup request', ['Businesses book a cash pickup online; operations confirms the time window by phone.'], { skip: [2] }],
    ['contact', 'Contact', ['Message form with topic, both offices and the published email addresses.']],
  ] },
  { id: 'portal', eyebrow: 'Part 2', title: 'Online application & applicant portal', intro: 'The current careers page asks applicants to download a form and deliver it to the Freeport office. Here they apply from a phone in five saved steps, upload their documents, then follow every stage and book their own entrance exam, drug test and interview.', screens: [
    ['apply-1', 'Apply online, step 1', ['Step 1 creates the applicant account, so nobody has to finish in one sitting.']],
    ['apply-2', 'Step 2: position & availability', ['Each step is saved to the server, so the form also works on a slow connection or without JavaScript.'], { skip: [] }],
    ['apply-3', 'Step 3: licences & experience', [], { skip: [0] }],
    ['apply-4', 'Step 4: documents', [], { skip: [0] }],
    ['apply-5', 'Step 5: review & send', ['On sending, the applicant receives a reference by email and the recruitment inbox is alerted.'], { skip: [0] }],
    ['portal-login', 'Applicant sign-in', ['Separate from staff logins. Applicants reset their own password by email.']],
    ['portal-dashboard', 'Applicant dashboard', ['Drafts to finish, sent applications with what to do next, messages and other openings.']],
    ['portal-application', 'Application tracker & self-booking', ['Applicants book a seat at an exam sitting, drug test or interview on their own island, and can cancel more than 24 hours ahead.', 'Missing or sent-back documents are uploaded here; the timeline shows every step.']],
    ['portal-inbox', 'Messages', ['A copy of every email, so nothing is lost in a spam folder.']],
    ['portal-profile', 'Profile', ['Contact details and password.']],
  ] },
  { id: 'admin', eyebrow: 'Part 3', title: 'The back office', intro: 'One private system for recruitment and client operations, with three roles: administrators see everything, recruiters see hiring, and operations see quotes, pickups and messages. It replaces paper files and email threads.', screens: [
    ['staff-login', 'Staff sign-in', ['Individual staff accounts; deactivated accounts are signed out immediately.']],
    ['admin-dashboard', 'Today', ['Each role sees the one number it has to act on first.']],
    ['admin-pipeline', 'Hiring pipeline', ['Drag and drop between stages; the applicant is notified by email and in the portal.']],
    ['admin-applicants', 'Applicants', ['Search, filter, bulk email and CSV export.']],
    ['admin-applicant', 'Applicant record', ['Document review, screening results, templated emails and an internal notes timeline.']],
    ['admin-slots', 'Screening sessions', ['Publish exam, drug-test and interview times once; applicants book themselves.']],
    ['admin-requests', 'Operations board', ['Quotes, armoured pickups and messages each on their own board.']],
    ['admin-request', 'Client request', ['Everything the client entered, with status, owner and notes.']],
    ['admin-jobs', 'Job postings', ['Post, pause or close openings per island.']],
    ['admin-templates', 'Email templates', ['Recruiters edit the wording of every applicant email.']],
    ['admin-settings', 'Office settings', ['Office details are edited here, not in code.']],
    ['admin-users', 'Staff accounts', ['Add, change role or deactivate.']],
  ] },
];

const FEATURES = [
  ['Public website', ['Mobile-first, light and dark themes', 'A page per service', 'History, leadership and locations', 'Careers with openings by island', 'Guard-service quote form', 'Armoured pickup request', 'Tap-to-call bar on phones', 'Old addresses redirected', 'Sitemap and Google for Jobs data']],
  ['Online application & portal', ['Five saved steps, finish later', 'Six document uploads, private storage', 'Status tracker and timeline', 'Self-booking of exam, drug test and interview', 'Re-upload of sent-back documents', 'Inbox mirroring every email', 'Withdraw, profile, password reset']],
  ['Recruitment back office', ['Drag-and-drop hiring pipeline', 'Document accept or send back', 'Exam, drug test and background results', 'Screening sessions with seats', 'Templated and bulk email', 'Filters and CSV export', 'Job postings per island']],
  ['Client operations', ['Boards for quotes, pickups and messages', 'Overdue flags after 24 hours', 'Owner, status and internal notes', 'Email alert for every new request']],
  ['Platform', ['Laravel 13 on PHP 8.3', 'Three staff roles', 'Separate applicant accounts', 'HTTPS, rate-limited forms, spam trap', `${TESTS} automated tests`]],
];

// Page geometry (inches): Letter with 0.55in top / 0.6in sides / 0.6in bottom margins.
const CONTENT_W = 7.3, CONTENT_H = 9.8, HEAD_H = 0.62, BULLET_H = 0.22, CAP_H = 0.42, GAP = 0.12;

function slicesOf(key, opts = {}) {
  const c = chunks[key];
  if (!c) throw new Error(`Missing screenshot: ${key}`);
  return c.chunks.map((f, i) => ({ f, i, ratio: sizeOf(f).h / sizeOf(f).w, cap: CAPTIONS[key]?.[i] ?? null })).filter(s => !(opts.skip ?? []).includes(s.i));
}

/** Pixel size of a JPEG, read from its SOF header. */
function sizeOf(f) {
  const b = fs.readFileSync(f);
  for (let i = 2; i < b.length;) {
    if (b[i] !== 0xff) { i++; continue; }
    const m = b[i + 1], len = b.readUInt16BE(i + 2);
    if (m >= 0xc0 && m <= 0xc3) return { h: b.readUInt16BE(i + 5), w: b.readUInt16BE(i + 7) };
    i += 2 + len;
  }
  throw new Error(`Not a JPEG: ${f}`);
}

/** Pack consecutive slices of one screen onto as few pages as fit; each keeps its own caption. */
function screenPages(sec, [key, title, bullets, opts]) {
  const c = chunks[key];
  const list = slicesOf(key, opts);
  const MIN_SCALE = 0.68; // two slices may share a page if both stay at least this wide
  const fits = (items, avail) => {
    const img = items.reduce((n, s) => n + s.ratio * CONTENT_W, 0);
    const over = items.reduce((n, s) => n + (s.cap ? CAP_H : 0) + GAP, 0);
    return { scale: Math.min(1, (avail - over) / img), ok: items.length === 1 || (avail - over) / img >= MIN_SCALE };
  };
  const pages = [];
  let cur = null;
  for (const s of list) {
    if (cur && fits([...cur.items, s], cur.avail).ok) { cur.items.push(s); continue; }
    if (cur) pages.push(cur);
    const first = pages.length === 0;
    cur = { first, avail: CONTENT_H - HEAD_H - (first && bullets.length ? bullets.length * BULLET_H + 0.12 : 0), items: [s] };
  }
  if (cur) pages.push(cur);
  for (const p of pages) p.scale = fits(p.items, p.avail).scale;
  return pages.map((p, pi) => `
    <section class="page shot">
      <header class="shot-head">
        <div><p class="eyebrow">${esc(sec.title)}${pi ? ' · continued' : ''}</p><h2>${esc(title)}</h2></div>
        <p class="url">${esc(c.url.replace(/CSL-[A-Z0-9]+/g, 'CSL-…').replace(/\/\d+$/, '/…'))}</p>
      </header>
      ${p.first && bullets.length ? `<ul class="bullets">${bullets.map(b => `<li>${esc(b)}</li>`).join('')}</ul>` : ''}
      ${p.items.map(s => {
        const h = s.ratio * CONTENT_W * p.scale;
        return `<figure><img src="${file(s.f)}" style="height:${h.toFixed(2)}in" alt="${esc(title)}">${s.cap ? `<figcaption class="cap">${esc(s.cap)}</figcaption>` : ''}</figure>`;
      }).join('')}
    </section>`).join('');
}

const phones = (items) => `<div class="phones">${items.map(([k, c, note]) => `<figure><img src="${file(chunks[k].chunks[0])}" alt="${esc(c)}"><figcaption><b>${esc(c)}</b>${note ? `<span>${esc(note)}</span>` : ''}</figcaption></figure>`).join('')}</div>`;
const foot = `<footer class="foot"><span class="fb">${MARK(14)}Candid Security · Website proposal</span><span>Code Canvas Consultants Ltd · ${esc(DATE)}</span></footer>`;
const screenCount = SECTIONS.reduce((n, s) => n + s.screens.length, 0);

const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Candid Security – Website Proposal</title>
<style>
  ${FONTS}
  @page { size: Letter; margin: 0.55in 0.6in 0.6in; }
  * { box-sizing: border-box; }
  :root { --char-950: #0e1113; --char-900: #171b1e; --char-800: #22282c; --aqua: #36cfbf; --aqua-text: #006b62; --aqua-soft: #dcf3ef; --gold: #c9a227; --gold-text: #7d6000; --bg: #f5f2eb; --surface: #fffdf9; --line: #ddd7ca; --ink: #15191c; --ink-2: #404950; --ink-3: #59636a; }
  html, body { margin: 0; padding: 0; font-family: "Work Sans", system-ui, sans-serif; color: var(--ink); font-size: 10.2pt; line-height: 1.45; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  h1, h2, h3 { font-family: Archivo, "Arial Black", Arial, sans-serif; font-weight: 800; font-stretch: 118%; letter-spacing: -0.01em; color: var(--ink); margin: 0; line-height: 1.02; }
  .page { page-break-after: always; break-after: page; min-height: 9.8in; display: flex; flex-direction: column; }
  .page:last-child { page-break-after: auto; }
  .eyebrow { font-family: "JetBrains Mono", Menlo, monospace; font-size: 7.6pt; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; color: var(--aqua-text); margin: 0 0 6px; display: flex; align-items: center; gap: 6px; }
  .eyebrow::before { content: ""; width: 9px; height: 6px; background: linear-gradient(135deg, transparent 45%, var(--gold) 45% 60%, transparent 60%), linear-gradient(225deg, transparent 45%, var(--gold) 45% 60%, transparent 60%); background-size: 50% 100%; background-position: left, right; background-repeat: no-repeat; }
  .muted { color: var(--ink-3); }
  .route { display: flex; align-items: center; gap: 0; margin: 14px 0; }
  .route .checkpoint { width: 10px; height: 10px; border: 2px solid var(--aqua); border-radius: 50%; flex: none; }
  .route .dots { flex: 1; height: 2px; background-image: radial-gradient(circle, var(--aqua) 1px, transparent 1.3px); background-size: 7px 2px; }
  .brand { display: flex; align-items: center; gap: 10px; } .brand .n { display: block; font-family: Archivo, Arial; font-weight: 800; font-stretch: 125%; text-transform: uppercase; font-size: 20pt; line-height: .9; letter-spacing: .02em; } .brand .s { display: block; margin-top: 4px; font-family: "JetBrains Mono", monospace; font-size: 6.6pt; letter-spacing: .2em; text-transform: uppercase; }
  .brand.light .n { color: #fff; } .brand.light .s { color: rgba(255,255,255,.7); } .brand.dark .n { color: var(--ink); } .brand.dark .s { color: var(--ink-3); }
  /* cover + dividers */
  .dark-page { background: var(--char-900); color: #fff; border-radius: 12px; padding: 0.65in 0.6in; min-height: 9.8in; position: relative; overflow: hidden; }
  .dark-page::after { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 5px; background: linear-gradient(90deg, var(--aqua) 70%, var(--gold) 70%); }
  .dark-page h1 { color: #fff; } .dark-page .eyebrow { color: var(--aqua); }
  .cover { display: flex; flex-direction: column; justify-content: space-between; }
  .cover h1 { font-size: 40pt; margin: 14px 0 16px; max-width: 6.3in; } .cover h1 em { font-style: normal; color: var(--aqua); }
  .cover .lead { color: rgba(255,255,255,.84); font-size: 12.5pt; max-width: 5.9in; margin: 0; }
  .cover .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; font-size: 9.3pt; color: rgba(255,255,255,.78); }
  .cover .meta b { display: block; font-family: "JetBrains Mono", monospace; font-weight: 500; font-size: 7.4pt; letter-spacing: .16em; text-transform: uppercase; color: var(--gold); margin-bottom: 4px; }
  .cover .meta strong { color: #fff; font-weight: 600; }
  .cover .photo { height: 2.35in; border-radius: 8px; background-size: cover; background-position: center 40%; position: relative; }
  .cover .photo::after { content: ""; position: absolute; inset: 0; border-radius: 8px; background: linear-gradient(90deg, rgba(23,27,30,.55), rgba(23,27,30,0) 60%); }
  .divider { display: flex; flex-direction: column; justify-content: center; }
  .divider h1 { font-size: 38pt; margin: 8px 0 16px; } .divider p { color: rgba(255,255,255,.84); font-size: 12pt; max-width: 5.9in; }
  .divider ol { color: rgba(255,255,255,.82); columns: 2; font-size: 10pt; margin: 22px 0 0; padding-left: 20px; } .divider li { break-inside: avoid; margin: 3px 0; } .divider li::marker { color: var(--aqua); font-family: "JetBrains Mono"; font-size: 8.5pt; }
  /* prose */
  .prose h1 { font-size: 26pt; margin-bottom: 10px; }
  .prose h2 { font-size: 14pt; margin: 16px 0 6px; }
  .prose p { margin: 0 0 8px; }
  .prose ul.plain { margin: 0 0 8px; padding-left: 16px; } .prose ul.plain li { margin: 2px 0; }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .card { border: 1px solid var(--line); border-radius: 8px; padding: 11px 13px; background: var(--surface); break-inside: avoid; }
  .card h3 { font-size: 11pt; margin-bottom: 6px; }
  .card ul { margin: 0; padding-left: 15px; } .card li { margin: 2px 0; }
  .evidence { counter-reset: e; list-style: none; padding: 0; margin: 8px 0 10px; }
  .evidence li { position: relative; padding: 7px 10px 7px 38px; border-bottom: 1px solid var(--line); }
  .evidence li::before { counter-increment: e; content: "0" counter(e); position: absolute; left: 6px; top: 7px; font-family: "JetBrains Mono"; font-size: 8pt; color: var(--gold-text); border: 1px solid var(--line); border-radius: 3px; padding: 0 4px; background: var(--surface); }
  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 12px 0 6px; }
  .stat { background: var(--char-900); color: #fff; border-radius: 8px; padding: 11px 12px; } .stat b { display: block; font-family: Archivo; font-weight: 800; font-stretch: 118%; font-size: 24pt; line-height: 1; color: var(--aqua); } .stat span { font-size: 8.4pt; color: rgba(255,255,255,.8); }
  .note { font-size: 8.4pt; color: var(--ink-3); }
  table.feat { width: 100%; border-collapse: collapse; font-size: 9.3pt; } table.feat td { border-bottom: 1px solid var(--line); padding: 8px 6px; vertical-align: top; } table.feat td:first-child { width: 24%; font-weight: 600; }
  .pill-row { display: flex; flex-wrap: wrap; gap: 5px; } .pill { border: 1px solid #b7e6df; background: var(--aqua-soft); color: var(--aqua-text); border-radius: 999px; padding: 2px 8px; font-size: 8.3pt; font-weight: 500; }
  /* screenshot pages */
  .shot-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; border-bottom: 2px solid var(--char-900); padding-bottom: 6px; margin-bottom: 8px; }
  .shot-head h2 { font-size: 17pt; } .url { font-family: "JetBrains Mono", monospace; font-size: 7.6pt; color: var(--ink-3); margin: 0; white-space: nowrap; }
  .bullets { margin: 0 0 8px; padding-left: 15px; font-size: 9.2pt; } .bullets li { margin: 1px 0; } .bullets li::marker { color: var(--aqua-text); }
  figure { margin: 0 0 ${GAP}in; display: block; break-inside: avoid; }
  figure img { display: block; max-width: 100%; width: auto; margin: 0 auto; border: 1px solid var(--line); border-radius: 5px; }
  .cap { margin: 5px 0 0; font-size: 8.4pt; line-height: 1.35; color: var(--ink-2); border-left: 3px solid var(--aqua); padding-left: 8px; }
  .phones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 12px; }
  .phones img { width: 100%; border-radius: 22px; border: 7px solid var(--char-900); display: block; }
  .phones figcaption { text-align: center; font-size: 9pt; margin-top: 7px; } .phones figcaption span { display: block; color: var(--ink-3); font-size: 8.2pt; }
  /* pricing */
  .price-hero { display: grid; grid-template-columns: 1.25fr 1fr; gap: 12px; margin: 6px 0 10px; }
  .price-main { background: var(--char-900); color: #fff; border-radius: 10px; padding: 14px 16px; } .price-main .amt { font-family: Archivo; font-weight: 800; font-stretch: 118%; font-size: 30pt; line-height: 1; color: var(--aqua); } .price-main .amt small { font-size: 12pt; color: #fff; font-stretch: 100%; } .price-main p { margin: 6px 0 0; color: rgba(255,255,255,.82); font-size: 9.2pt; }
  .year1 { border: 2px solid var(--gold); border-radius: 10px; padding: 12px 14px; background: var(--surface); } .year1 .row { display: flex; justify-content: space-between; align-items: baseline; padding: 3px 0; border-bottom: 1px dashed var(--line); font-size: 9.4pt; } .year1 .row:last-child { border: 0; } .year1 .row b { font-family: Archivo; font-stretch: 112%; font-size: 13pt; }
  .terms { font-size: 9pt; } .terms li { margin: 2px 0; }
  table.sla { width: 100%; border-collapse: collapse; font-size: 8.8pt; margin-top: 4px; } table.sla th, table.sla td { border-bottom: 1px solid var(--line); padding: 4px 6px; text-align: left; } table.sla th { font-family: "JetBrains Mono"; font-weight: 500; font-size: 7.2pt; letter-spacing: .1em; text-transform: uppercase; color: var(--ink-3); }
  .timeline { list-style: none; margin: 8px 0 12px; padding: 0 0 0 22px; position: relative; }
  .timeline::before { content: ""; position: absolute; left: 6px; top: 6px; bottom: 6px; width: 2px; background-image: radial-gradient(circle, var(--aqua-text) 1px, transparent 1.3px); background-size: 2px 7px; }
  .timeline li { position: relative; margin: 0 0 9px; } .timeline li::before { content: ""; position: absolute; left: -21px; top: 3px; width: 10px; height: 10px; border: 2px solid var(--aqua-text); background: var(--surface); border-radius: 50%; }
  .timeline b { font-family: "JetBrains Mono"; font-weight: 500; font-size: 8pt; letter-spacing: .1em; text-transform: uppercase; color: var(--aqua-text); margin-right: 6px; }
  .next li { margin: 5px 0; }
  .contact-card { margin-top: 16px; background: var(--char-900); color: #fff; border-radius: 10px; padding: 16px 18px; display: flex; justify-content: space-between; gap: 16px; align-items: center; } .contact-card p { margin: 2px 0; color: rgba(255,255,255,.85); } .contact-card strong { color: #fff; font-size: 12pt; }
  footer.foot { margin-top: auto; padding-top: 8px; font-size: 7.6pt; color: var(--ink-3); display: flex; justify-content: space-between; border-top: 1px solid var(--line); align-items: center; } footer.foot .fb { display: inline-flex; align-items: center; gap: 6px; }
</style></head><body>

<section class="page dark-page cover">
  <div>${WORDMARK('light')}</div>
  <div>
    <p class="eyebrow">Website proposal · ${esc(DATE)}</p>
    <h1>A new website, online hiring <em>and an operations back office</em> for Candid Security</h1>
    <p class="lead">A complete rebuild of candidbahamas.com: apply online instead of on paper, an applicant portal with self-booked screening, and one back office for recruitment, quotes and armoured pickups. Built and running as a working demo today.</p>
  </div>
  <div class="photo" style="background-image:url('${file(COVER)}')"></div>
  <div class="meta">
    <div><b>Prepared for</b><strong>Candid Security Ltd.</strong><br>Polaris Drive, Freeport, Grand Bahama<br>and Nassau, New Providence<br>Attention: Cleveland A. Duncombe, President &amp; CEO</div>
    <div><b>Prepared by</b><strong>Keron Lewis</strong>, Code Canvas Consultants Ltd<br>Port of Spain · codecanvastt.com<br>+1 868 466-CODE · keronlewis@live.com</div>
  </div>
</section>

<section class="page prose">
  <p class="eyebrow">Executive summary</p>
  <h1>Why rebuild now</h1>
  <p>Candid has grown from about 15 officers at one Grand Bahama hotel in 1997 to five services, offices in Freeport and Nassau and cover in Abaco and Bimini; The Tribune reported around 200 staff when the Polaris Drive head office opened in 2016. The website has not kept pace. A review of candidbahamas.com in October 2026 found:</p>
  <ol class="evidence">
    <li><b>It does not adapt to phones.</b> The pages have no mobile viewport setting, so on a phone they render as a shrunken desktop page.</li>
    <li><b>Pages have an empty title.</b> Browser tabs and search results have no page name to show.</li>
    <li><b>Menu links point to a developer’s staging address</b> (designandexecute.com) rather than candidbahamas.com.</li>
    <li><b>Hiring is on paper.</b> The careers page asks applicants to download an application, complete it and deliver it to the Freeport office. There is no way to apply online.</li>
    <li><b>The theme dates from around 2011.</b> It is a legacy custom WordPress theme that still carries Internet Explorer 6-era code.</li>
  </ol>
  <p>For a company that recruits officers across several islands, the paper application is the costliest gap: every form is handled by hand, applicants have to travel to deliver it, and they cannot see where their application stands. This proposal replaces it with an online application, an applicant portal and a back office where recruiters and operations staff work from the same system. The demo is complete and can be shown live.</p>
  <div class="stat-grid">
    <div class="stat"><b>${screenCount}</b><span>screens built, shown in this proposal</span></div>
    <div class="stat"><b>5</b><span>saved steps to apply from a phone</span></div>
    <div class="stat"><b>3</b><span>staff roles: admin, recruiter, operations</span></div>
    <div class="stat"><b>${TESTS}</b><span>automated tests run on every change</span></div>
  </div>
  <p class="note">Company facts are from candidbahamas.com (history, services, executive team, mission and careers pages) and The Tribune, 24 August 2016. Names, titles and addresses will be confirmed with Candid before launch.</p>
  ${foot}
</section>

<section class="page prose">
  <p class="eyebrow">At a glance</p>
  <h1>What is included</h1>
  <table class="feat"><tbody>
    ${FEATURES.map(([a, f]) => `<tr><td>${esc(a)}</td><td><div class="pill-row">${f.map(x => `<span class="pill">${esc(x)}</span>`).join('')}</div></td></tr>`).join('')}
  </tbody></table>
  <h2>How the pieces connect</h2>
  <p>An applicant finds an opening on the <b>careers page</b>, applies in five saved steps and uploads a police certificate and ID from their phone. The application arrives in the <b>back office</b>, where a recruiter checks the documents and moves it along the pipeline. At the entrance exam, drug test and interview stages the applicant books a seat from the <b>applicant portal</b>, on their own island, from the sessions recruitment has published. Every move writes to the applicant’s timeline and sends an email.</p>
  <p>On the client side, guard-service quotes, armoured pickup requests and messages each get a reference number and land on the <b>operations board</b>, where anything left in “New” for more than 24 hours is flagged on the dashboard.</p>
  <h2>Content and photography</h2>
  <p>Copy is taken from the current site and The Tribune and rewritten for clarity; nothing about clients, certifications or figures has been invented. Three photos are Candid’s own from candidbahamas.com, used at card size because the originals are small. The rest are licensed Bahamian photos from Wikimedia Commons, credited on the site. Original photos of officers, vehicles and the Polaris Drive office would replace them at launch. Job postings, applicants and client requests in the screenshots are demo data.</p>
  ${foot}
</section>

${SECTIONS.map(sec => `
<section class="page dark-page divider">
  <p class="eyebrow">${esc(sec.eyebrow)}</p>
  <h1>${esc(sec.title)}</h1>
  ${ROUTE}
  <p>${esc(sec.intro)}</p>
  <ol>${sec.screens.map(s => `<li>${esc(s[1])}</li>`).join('')}</ol>
</section>
${sec.screens.map(s => screenPages(sec, s)).join('')}
`).join('')}

<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design</p><h2>Built for the phone first</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0">Most applicants will find Candid on a phone. Every page is laid out for small screens first, with a call, apply and quote bar always within thumb reach.</p>
  ${phones([['m-home', 'Home', 'Tap-to-call card for both offices'], ['m-careers', 'Careers', 'Hiring steps before the openings'], ['m-apply', 'Apply, step 1', 'Creates the applicant account']])}
  ${foot}
</section>
<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design · continued</p><h2>Portal and back office on a phone</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0">Applicants book their own exam from the portal, and supervisors can check the dashboard or move an applicant from the field.</p>
  ${phones([['m-portal-application', 'Applicant portal', 'Book the entrance exam'], ['m-admin-dashboard', 'Back office: Today', 'The number to act on'], ['m-admin-pipeline', 'Hiring pipeline', 'Columns scroll sideways']])}
  ${foot}
</section>

<section class="page prose">
  <p class="eyebrow">Technology, hosting &amp; security</p>
  <h1>Built to last, simple to run</h1>
  <div class="cols">
    <div class="card"><h3>Platform</h3><ul><li>Laravel 13 on PHP 8.3 with a MySQL database: open-source, widely supported, no licence fees</li><li>Runs on standard PHP hosting with no background servers to maintain</li><li>${TESTS} automated tests plus static analysis on every change</li><li>Works without JavaScript; light and dark themes</li></ul></div>
    <div class="card"><h3>Security &amp; privacy</h3><ul><li>HTTPS throughout, with security headers</li><li>Applicant documents stored privately, outside the public web folder, and shown only to staff or to the applicant who uploaded them</li><li>Separate applicant and staff accounts; three staff roles; deactivated staff are signed out</li><li>Rate-limited sign-in and forms, a spam trap, and no National Insurance number collected online</li></ul></div>
    <div class="card"><h3>Search &amp; reach</h3><ul><li>Page titles and descriptions on every page, an XML sitemap and organisation data</li><li>Job postings carry Google for Jobs data</li><li>Old candidbahamas.com addresses redirect to their new pages, so existing links keep working</li></ul></div>
    <div class="card"><h3>Emails included</h3><ul><li>To applicants: application received, every stage change, booking confirmations, documents sent back, recruiter messages, password reset</li><li>To the office: new application, new quote, new pickup request, new message</li><li>A failed email never blocks the action; it is noted on the applicant’s record</li></ul></div>
  </div>
  <h2>Ownership</h2>
  <p><b>Your domain.</b> We’ll confirm candidbahamas.com is registered in Candid Security’s name, so you always control it.</p>
  <p>Candid’s content, domain, documents and data belong to Candid at all times. Office details, job postings, email templates, screening sessions and staff accounts are all managed from the back office without a developer.</p>
  ${foot}
</section>

<section class="page prose">
  <p class="eyebrow">Investment</p>
  <h1>One monthly plan. No build cost.</h1>
  <div class="price-hero">
    <div class="price-main"><div class="amt">US$1,000 <small>per month</small></div><p>Invoiced in US dollars. <b style="color:#fff">US$0 for design and development</b>, with no setup fee and no deposit. Hosting, security, updates and ongoing changes are all included.</p></div>
    <div class="year1"><div class="row"><span>Year one</span><b>US$12,000</b></div><div class="row"><span>Year one, prepaid (10% off)</span><b>US$10,800</b></div><div class="row"><span>Design &amp; build</span><b>US$0</b></div></div>
  </div>
  <div class="cols">
    <div>
      <h2 style="margin-top:4px">Terms</h2>
      <ul class="plain terms">
        <li>Billing starts at the end of the month in which the site launches, prorated for the days it was live. Monthly invoices are due within 14 days.</li>
        <li>Pay online at portal.codecanvastt.com, by bank transfer or by Visa/Mastercard.</li>
        <li>No minimum term. 30 days’ notice by email from either side; the site keeps running through the notice period.</li>
        <li>Price fixed for the first 24 months; any later change is given in writing with 60 days’ notice. Optional annual prepayment at 10% off: US$10,800 a year.</li>
        <li><b>Handover fee US$7,000, only if Candid leaves.</b> It buys the site outright and moves it to hosting in Candid’s name, with all files and logins. It is the same after one month or five years, and is waived if Code Canvas stops trading.</li>
        <li>Candid’s content, domain and data are always Candid’s.</li>
      </ul>
    </div>
    <div>
      <h2 style="margin-top:4px">Included in the plan</h2>
      <ul class="plain terms">
        <li>Complete design and build of everything in this proposal</li>
        <li>Managed hosting with CDN and SSL</li>
        <li>Daily backups plus a weekly off-site copy</li>
        <li>Security patches and software updates</li>
        <li>24/7 uptime monitoring</li>
        <li><b>Up to 10 hours a month</b> of content changes and small improvements</li>
        <li>Staff training for administrators, recruiters and operations</li>
        <li>Google Analytics and Search Console, with a monthly summary</li>
        <li>Email, WhatsApp and phone support, plus a quarterly review call</li>
        <li>A 30-day post-launch warranty</li>
      </ul>
    </div>
  </div>
  <h2>Service levels</h2>
  <table class="sla"><thead><tr><th>Issue</th><th>Response</th><th>Fix target</th></tr></thead><tbody>
    <tr><td>Site down, or forms and emails not sending</td><td>1 hour, any day</td><td>4 hours</td></tr>
    <tr><td>Something on a page is broken</td><td>4 business hours</td><td>1 business day</td></tr>
    <tr><td>Routine content change</td><td>Acknowledged in 1 business day</td><td>Done in 2 business days</td></tr>
  </tbody></table>
  <h2>Extra work</h2>
  <p style="margin-bottom:4px">Work beyond 10 hours in a month is US$50 per hour, or a fixed quote agreed in advance. Nothing outside the plan starts without written approval. Quoted separately:</p>
  <div class="pill-row"><span class="pill">Online card payments (e.g. invoices for armoured clients)</span><span class="pill">SMS reminders for exams and interviews</span><span class="pill">WhatsApp notifications</span><span class="pill">A client portal for guard and armoured customers</span><span class="pill">Payroll, HR or rostering integrations</span><span class="pill">Third-party API integrations</span><span class="pill">Paid advertising and SEO campaigns</span><span class="pill">Professional photography or video</span></div>
  ${foot}
</section>

<section class="page prose">
  <p class="eyebrow">Timeline</p>
  <h1>Six weeks from approval to launch</h1>
  <ol class="timeline">
    <li><b>Week 1</b>Kick-off call. Brand assets, photos and content from Candid; confirm addresses, office hours, leadership titles and current openings.</li>
    <li><b>Week 2</b>Revision round 1. Forms, emails and staff roles configured with Candid’s real addresses and mailboxes.</li>
    <li><b>Week 3</b>Data and content load: job postings, screening sessions, email templates, office settings and staff accounts.</li>
    <li><b>Week 4</b>Revision round 2.</li>
    <li><b>Week 5</b>Formal testing with Candid staff: every form, the full application, the portal and every back-office screen, on phones and desktops.</li>
    <li><b>Week 6</b>Launch on candidbahamas.com, old addresses redirected, Search Console set up, and training for administrators, recruiters and operations.</li>
  </ol>
  <h2>What we need from Candid</h2>
  <ul class="plain">
    <li>A vector or high-resolution copy of the badge (the largest online is 143 px, from the current site header), any brand guidance, and original photos of officers, vehicles and the offices.</li>
    <li>Confirmation of the Freeport street number and the Nassau address, office hours, phones and faxes.</li>
    <li>Confirmation of leadership names and titles, and the openings to publish at launch.</li>
    <li>The mailboxes for applications and client requests (for example hr@ and president@candidbahamas.net).</li>
    <li>Access to the candidbahamas.com domain settings, and review of the privacy notice by Candid’s counsel.</li>
  </ul>
  <h2>Approval and billing</h2>
  <p>The site is on a private preview link throughout, and nothing goes live until Candid approves it in writing. <b>Billing does not start until launch:</b> the build, both revision rounds, testing and the preview cost nothing.</p>
  ${foot}
</section>

<section class="page prose">
  <p class="eyebrow">Next steps</p>
  <h1>From proposal to launch</h1>
  <ol class="next">
    <li><b>See it working.</b> A live walkthrough of the demo: apply as a candidate, book an exam, then follow the same application through the back office.</li>
    <li><b>Send changes.</b> Any wording, page or workflow changes; the first revision round is open now.</li>
    <li><b>Sign.</b> A one-page agreement with these terms: US$1,000 a month, US$0 build, month to month on 30 days’ notice, and a US$7,000 handover fee only if Candid leaves.</li>
    <li><b>Kick off</b> the same week, and launch six weeks later.</li>
  </ol>
  <div class="contact-card">
    <div><strong>Keron Lewis</strong><p>Director, Code Canvas Consultants Ltd · Port of Spain, Trinidad and Tobago</p><p>codecanvastt.com · +1 868 466-CODE · keronlewis@live.com</p></div>
    ${MARK(56)}
  </div>
  ${foot}
</section>
</body></html>`;

fs.writeFileSync(OUT_HTML, html);
const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
const page = await browser.newPage();
await page.goto('file://' + OUT_HTML, { waitUntil: 'networkidle0' });
await page.evaluateHandle('document.fonts.ready');
await page.pdf({ path: OUT_PDF, format: 'Letter', printBackground: true, preferCSSPageSize: true, displayHeaderFooter: false });
await browser.close();
console.log('PDF written:', OUT_PDF, Math.round(fs.statSync(OUT_PDF).size / 1024 / 1024 * 10) / 10 + ' MB');
