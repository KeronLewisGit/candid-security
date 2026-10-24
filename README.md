# Candid Security

Website, online job application, applicant portal and back office for Candid Security Ltd., a Bahamian security company founded in Freeport, Grand Bahama in 1997 (offices in Freeport and Nassau, cover in Abaco and Bimini).

## What it does

- **Public site.** Home, a page for each service (security officers, armoured transport, mobile patrol, aviation security, lifeguards), about with history and leadership, locations, careers, contact, privacy, photo credits and an HTML sitemap. Mobile-first, light and dark themes, works without JavaScript, sticky call/apply/quote bar on phones. Old WordPress addresses (`/contact-information-3/`, `/employment-opportunities-2/` and so on) redirect permanently.
- **Apply online instead of on paper.** The current site says to download a form and deliver it to the Freeport office. The new application is five short, server-saved steps (about you, position, licences and experience, documents, review). Step one creates an applicant account, so people can stop and finish later. Uploads: police certificate, passport or ID, photo, references, permit, CV (PDF, image or Word, 8 MB each, stored privately). It never asks for a National Insurance number online (the paper form did).
- **Applicant portal** at `/portal` (separate `applicant` guard). Status tracker, upload missing or sent-back documents, self-book entrance exam, drug test and interview sessions on their island, cancel more than 24 hours ahead, inbox of updates (also emailed), withdraw, profile and password reset. Another applicant's reference, document or booking is a 404.
- **Recruitment back office.** Pipeline for guard hiring: new → document check → entrance exam → drug test → interview → background check → offer → hired / rejected / withdrawn. Kanban with drag and drop (plus a no-JS move menu), list with island/stage/position/posting/recruiter/missing-documents filters, bulk email from templates with placeholders, CSV export, document accept or send back with a reason, exam score (pass mark setting, 70% from the 2011 form), drug-test and background-check results, notes, screening sessions with seats.
- **Client intake + operations board.** Guard-service quote (site type, island, armed/unarmed, posts, hours, duration), armoured-transport pickup request (address, destination, date, window, frequency) and contact messages. Worked on a board per type with statuses; moving out of New records the first response.
- **Dashboard** leads with the number to act on: applicants waiting on the document check (recruiters), client requests unanswered for more than 24 hours (operations), or both (admins).
- **Roles:** admin, recruiter, operations. Office phones, addresses, hours and emails are editable in Admin → Office settings.

## Requirements

PHP 8.3 or 8.4, Composer 2, Node 20+ for building assets, SQLite locally or MySQL in production.

## Local setup

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seeder=DemoSeeder
npm install && npm run build
php artisan serve --port=8102
```

Demo logins (password `password`): `admin@candid.test` (admin), `hr@candid.test` (recruiter), `ops@candid.test` (operations) at `/login`; `applicant@candid.test` at `/portal/login` (at the entrance-exam stage with sessions to book). All demo jobs, applicants and client requests are illustrative.

## Checks

```sh
php artisan test                 # feature tests, plus a PHP 8.3 readiness test
vendor/bin/phpstan analyse       # Larastan level 6, analysed as PHP 8.3
vendor/bin/pint --test
```

## Deploying

See [INSTALL-HOSTINGER.md](INSTALL-HOSTINGER.md). `scripts/package-hostinger.sh` builds `dist/candid-hostinger.zip`, which extracts straight into `public_html/` (app in `candid-app/`).

## Before launch: confirm with the client

- Addresses. candidbahamas.com still lists Sun Plaza, West Settlers Way; The Tribune (Aug 2016) reports new offices on Polaris Drive, which is the default here. The Nassau address (#8 Carmichael Road Plaza) and the fax numbers come from the same old contact page. Office hours are not published anywhere and are left blank.
- Leadership and management names/titles come from the ~2011 executive team page; only Cleveland A. Duncombe (President & CEO) and Marcia Duncombe (VP) are confirmed by the 2016 Tribune article.
- Benefits, requirements and service descriptions are paraphrased from the current site. Insurance figures, association memberships and the client list on that site are deliberately not shown (several listed clients no longer exist).
- Real job postings. The demo postings are illustrative.
- `CANDID_NOTIFY_EMAIL`, the mailbox, and the privacy notice (draft for counsel).
- Photography. Three photos are Candid's own from candidbahamas.com (small originals, used only in cards); the rest are Wikimedia Commons under the licences on `/photo-credits`. Ask for originals of officers, vehicles and the Polaris Drive office.
