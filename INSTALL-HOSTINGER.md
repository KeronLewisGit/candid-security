# Candid Security – Hostinger installation guide

Requirements: **PHP 8.3** (8.4 also works), a **MySQL database**, and SSH access if possible. Without SSH, use the zip and its one-time `setup.php` installer (Option A).

## Option A – upload the zip into public_html (fastest, no Git or SSH)

Run `scripts/package-hostinger.sh` locally. It builds `dist/candid-hostinger.zip`, which includes the production `vendor/` and built assets.

```
public_html/
├── index.php  .htaccess  robots.txt  favicon.svg  setup.php
├── build/                 compiled CSS, JS and fonts
├── img/                   site photography (WebP, three sizes each)
└── candid-app/          the Laravel application (blocked from the web by its own .htaccess)
```

Before you start, do steps 1–4 of **Prepare hPanel** in Option B below (domain + SSL, PHP 8.3, MySQL database, mailbox).

1. In File Manager, empty `public_html/` (delete Hostinger's `default.php`), upload the zip to `public_html` and extract it there.
2. Copy `candid-app/.env.example` to `candid-app/.env`. Fill in the database, mail and `CANDID_*` values, and set `SETUP_TOKEN` to a long random string.
3. Open `https://www.candidbahamas.com/setup.php?token=YOUR_TOKEN`. It generates the app key, migrates, creates the first staff account and deletes itself.
4. Remove `SETUP_TOKEN` from `.env`.

To update later, build a new zip, and upload and extract it over the old files. `candid-app/.env`, `candid-app/storage/` (applicant documents, logs) is not in the zip, so they are kept. Then visit `/setup.php?token=…` again if the release has new migrations (add a `SETUP_TOKEN` first; once a staff account exists the installer only migrates and never creates or resets accounts), or run `php artisan migrate --force` over SSH from `candid-app/`.

## Option B – Hostinger Git deployment (for ongoing development)

The whole repository is cloned straight into `public_html/`:

```
public_html/
├── .htaccess        forces HTTPS, rewrites every request into public/
├── .env             created by you on the server (never committed)
├── app/ bootstrap/ config/ database/ resources/ routes/ storage/ vendor/
└── public/          the only folder the web can reach (index.php, build/, robots.txt)
```

- The root `.htaccess` sends every request into `public/`, so `.env`, `storage/`, `vendor/` and `.git/` are never served.
- `composer.lock` is resolved for PHP 8.3 through `config.platform.php` in `composer.json`.
- `public/build/` holds the compiled CSS, JS and fonts. It is committed because the server has no Node, so run `npm run build` and commit the result after any CSS or JS change.
- Nothing needs a cron job or a queue worker: there are no scheduled tasks and mail is sent inline (`QUEUE_CONNECTION=sync`).

### 1. Prepare hPanel (one time)

1. **Domain and SSL.** Point `candidbahamas.com` at the hosting plan, then go to hPanel → Security → SSL and install the free certificate. The `.htaccess` redirects every request to HTTPS, so the site will not load until the certificate is active.
2. **PHP version.** Go to hPanel → Advanced → PHP Configuration and choose **PHP 8.3** (8.4 also works). Leave the default extensions enabled. `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` and `tokenizer` are required. Set `upload_max_filesize` and `post_max_size` to at least **20M**: applicants upload police certificates and IDs of up to 8 MB each.
3. **Database.** Go to hPanel → Databases → MySQL Databases and create a database and a user. Write down the full names, which include the `u123456789_` prefix, and the password.
4. **Mailbox.** Go to hPanel → Emails and create `careers@candidbahamas.com`. The site sends applicant updates and office notifications (applications, quotes, pickups) through this mailbox.
5. **SSH.** Go to hPanel → Advanced → SSH Access, enable it, and note the IP, port (usually `65002`) and username.

### 2. Connect the private GitHub repository

The repository (`YOUR-GITHUB-USER/candid-security`) is private, so Hostinger needs a read-only deploy key.

1. Go to hPanel → Advanced → **GIT** and click **Generate SSH key**. Copy the public key.
2. On GitHub, go to the repository → Settings → Deploy keys → **Add deploy key**. Paste the key, name it "Hostinger", and leave *Allow write access* unchecked.
3. Hostinger only deploys into an **empty** directory. Open File Manager and delete everything in `public_html/`, including Hostinger's `default.php`.
4. Back in hPanel → GIT → Create a new repository, fill in:
   - **Repository:** `git@github.com:YOUR-GITHUB-USER/candid-security.git` (the SSH address, not `https://`)
   - **Branch:** `master`
   - **Directory:** leave empty. An empty value deploys into `public_html/`.
5. Click **Create**, then **Deploy**. When `composer.json` is present, Hostinger runs `composer install` after it clones the repository.

### 3. First-time setup over SSH

```bash
ssh -p 65002 u123456789@YOUR_SERVER_IP
cd ~/domains/candidbahamas.com/public_html

php -v                                   # must be 8.3+. If not, replace `php` below with /opt/alt/php83/usr/bin/php
ls vendor/autoload.php                   # if this is missing, Composer did not run: see the next line
composer install --no-dev --optimize-autoloader --no-interaction

cp deploy/env.production.example .env
nano .env                                # DB_*, MAIL_PASSWORD, CANDID_* values. Leave APP_KEY empty
chmod -R 775 storage bootstrap/cache
php artisan key:generate --force
php artisan migrate --force
php artisan app:create-admin             # first staff login (prompts for name, email, password)
php artisan optimize
```

`SETUP_TOKEN` and the `ADMIN_*` lines in `.env` are only used by the zip installer (Option A). Leave them empty here.

Open `https://www.candidbahamas.com` and sign in at `/login` to check the install.

### 4. Turn on automatic deploys (optional)

Go to hPanel → GIT → your repository → **Auto Deployment** and copy the webhook URL. On GitHub, go to Settings → Webhooks → **Add webhook**, paste the URL as the *Payload URL*, and keep the *push* event. Every push to `master` then deploys on its own.

### 5. Every later deploy

1. Locally: run `npm run build` if any CSS or JS changed, run the checks (`php artisan test`, `vendor/bin/phpstan analyse`, `vendor/bin/pint`), then commit and push to `master`.
2. Hostinger pulls the code, either through the webhook or when you click **Deploy** in hPanel → GIT. `.env`, `storage/` contents and `vendor/` are not tracked in Git, so a pull leaves them alone.
3. Over SSH:

   ```bash
   cd ~/domains/candidbahamas.com/public_html
   composer install --no-dev --optimize-autoloader --no-interaction
   php artisan migrate --force
   php artisan optimize
   ```

   Do not skip `optimize`. The routes, config and views are cached, so new pages return 404 and new settings are ignored until you run it.

`optimize` also caches the configuration. After any later `.env` edit, run `php artisan optimize` again or the change is ignored.

### Troubleshooting

| Symptom | Fix |
|---|---|
| hPanel says the directory is not empty | Delete everything in `public_html/`, including hidden files, then deploy again. |
| "Permission denied (publickey)" during deploy | The deploy key is missing on GitHub, or the repository URL is the `https://` form. |
| 500 error with no message | Read `storage/logs/laravel-*.log`. Usual causes: no `.env`, empty `APP_KEY`, wrong `DB_*` values, or `storage/` not writable. |
| "Your Composer dependencies require a PHP version >= 8.3" | The website or SSH PHP is older than 8.3. Fix it in PHP Configuration, or run `/opt/alt/php83/usr/bin/php $(which composer) install --no-dev`. |
| Redirect loop or "too many redirects" | SSL is not active yet, or Cloudflare is in *Flexible* mode. Use *Full*. |
| Page loads without styling | `public/build/` was not committed. Run `npm run build` locally, commit, push and deploy again. |
| Emails never arrive | Check `MAIL_*` and `CANDID_NOTIFY_EMAIL` in `.env`, run `php artisan optimize`, and check the mailbox's spam folder. |

## Check after install

- `https://www.candidbahamas.com/.env`, `/.git/config` and `/composer.json` must return 403 or 404. For Option A, check `/candid-app/` as well.
- `http://` must redirect to `https://`.
- Send a test application from a phone (upload a photo of a document), a test quote and a test pickup request, and confirm the emails reach `CANDID_NOTIFY_EMAIL` and the applicant.
- In the back office, open the test application and view the document. Then confirm `/candid-app/storage/app/private/applications/` returns 403 (Option A) or that `storage/` is unreachable (Option B). Sign in to the applicant portal with a second test account and confirm the first applicant's reference returns 404.
- In Admin → Office settings, confirm the phones, addresses and hours, and add exam/interview sessions under Screening sessions so applicants can book.
- Old WordPress addresses such as `/contact-information-3/` and `/employment-opportunities-2/` should redirect (301) to the new pages.
- Submit `/sitemap.xml` in Google Search Console, and test a job page with Google's Rich Results Test.
- When the site replaces the live candidbahamas.com, set `CANDID_PREVIEW=false`. On a temporary preview domain, set it to `true` so a banner shows and search engines skip it.
