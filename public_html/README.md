# LawMillion.com

PHP + MySQL backend for **LawMillion.com** — a U.S. legal directory connecting Americans with verified attorneys in 15+ practice areas across all 50 states.

This codebase is built for the **U.S. market only**:
- `en-US` language and locale
- USD currency, MM/DD/YYYY dates
- America/New_York timezone
- US phone format `(XXX) XXX-XXXX`, US ZIP validation `12345` or `12345-6789`
- Schema.org `addressCountry: US`, `areaServed: United States`
- `geo.region` = US, `hreflang` en-US only
- Explicit `Allow:` directives in `robots.txt` for U.S. AI engines (GPTBot, ClaudeBot, PerplexityBot, Google-Extended)

---

## Tech stack

- PHP 8.1+ (no framework dependency, single-file front-controller pattern)
- MySQL 8.0+ / utf8mb4
- Apache 2.4 with `mod_rewrite`, `mod_deflate`, `mod_expires`
- Vanilla CSS + JavaScript (no build step)

---

## Project structure

```
lawmillion/
├── public/                  ← Apache document root points HERE
│   ├── index.php            Front controller (all requests funnel through this)
│   ├── .htaccess            URL rewrites, 301s, security, compression
│   ├── manifest.json        PWA manifest
│   └── assets/
│       ├── css/styles.css
│       ├── js/app.js
│       └── images/          (drop your logo + OG images here)
├── app/
│   ├── config/config.php    Site config (env-driven)
│   ├── core/
│   │   ├── Database.php     PDO singleton
│   │   ├── Router.php       Regex router with auto canonicalization + DB redirects
│   │   ├── SEO.php          Meta tags + JSON-LD generators
│   │   └── helpers.php      e(), us_phone(), usd(), us_date(), csrf_token()…
│   ├── controllers/
│   │   ├── HomeController.php
│   │   ├── PracticeAreaController.php
│   │   ├── AttorneyController.php
│   │   ├── DirectoryController.php
│   │   ├── BlogController.php
│   │   ├── AttorneyPortalController.php
│   │   └── SitemapController.php  (also contains ErrorController)
│   └── views/               PHP templates
├── database/
│   ├── schema.sql           CREATE TABLE statements
│   └── seed.sql             50 states + 25 cities + 15 practice areas + sample data
├── .env.example             Copy to .env and fill in
└── README.md
```

---

## URL architecture (SEO-optimized)

| Page type | URL pattern |
|---|---|
| Homepage | `/` |
| Practice area hub | `/practice-areas/` |
| Practice area | `/practice-areas/{slug}/` (15 of these) |
| Directory search | `/find-a-lawyer/` |
| Attorney profile | `/attorneys/{state}/{city}/{name-slug}/` |
| Blog hub | `/blog/` |
| Blog post | `/blog/{slug}/` |
| For attorneys (B2B) | `/for-attorneys/` |
| Login (noindex) | `/for-attorneys/login/` |
| Signup | `/for-attorneys/create-profile/` |
| Dashboard (noindex) | `/for-attorneys/dashboard/` |
| Sitemap | `/sitemap.xml` (dynamic) |
| Robots | `/robots.txt` (dynamic) |
| LLMs | `/llms.txt` (for AI engines) |

All URLs are lowercase with trailing slash. The router auto-301s anything else to canonical form.

---

## Setup

### 1. Server requirements

- Apache 2.4+ with `mod_rewrite` enabled
- PHP 8.1+ with `pdo_mysql`, `json`, `mbstring`, `openssl`
- MySQL 8.0+
- HTTPS certificate (the front controller forces HTTPS in production)

### 2. Database

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

Create a dedicated app user (replace the password):

```sql
CREATE USER 'lawmillion_app'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT SELECT, INSERT, UPDATE, DELETE ON lawmillion.* TO 'lawmillion_app'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Environment

```bash
cp .env.example .env
# edit .env with your real DB credentials
```

If your host doesn't support `.env` files natively, set environment variables in `apache.conf` / `httpd.conf` via `SetEnv DB_PASSWORD ...` or directly in `app/config/config.php` (NOT recommended for production).

### 4. Apache document root

Point your virtual host's `DocumentRoot` to **`lawmillion/public/`** — never to the project root. Example:

```apache
<VirtualHost *:443>
    ServerName lawmillion.com
    ServerAlias www.lawmillion.com
    DocumentRoot /var/www/lawmillion/public

    SetEnv APP_ENV production
    SetEnv DB_HOST 127.0.0.1
    SetEnv DB_NAME lawmillion
    SetEnv DB_USER lawmillion_app
    SetEnv DB_PASSWORD your_password_here

    <Directory /var/www/lawmillion/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/lawmillion.com/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/lawmillion.com/privkey.pem
</VirtualHost>
```

### 5. Permissions

```bash
chown -R www-data:www-data lawmillion/
chmod -R 755 lawmillion/
chmod 600  lawmillion/.env
```

---

## SEO + AEO features built-in

- **Per-page `<title>` and `meta description`** sourced from the `practice_areas`, `blog_posts`, and `attorneys` tables (editable without redeploying).
- **Canonical URLs** on every page, always `https://lawmillion.com/...` (no trailing query strings, no www).
- **Open Graph + Twitter Card** meta tags.
- **JSON-LD schema** (in `app/core/SEO.php`):
  - `Organization` + `WebSite` on every page
  - `BreadcrumbList` on every non-home page
  - `LegalService` + `LocalBusiness` on practice-area pages
  - `Attorney` + `LegalService` + `PostalAddress` on profile pages
  - `Article` + `LegalArticle` on blog posts
  - `FAQPage` on practice-area pages with FAQ data (stored in `practice_areas.faq_json`)
  - `AggregateRating` from real review counts
- **`hreflang="en-US"`** + `x-default` (US-only audience)
- **`geo.region: US`** meta tag
- **301 redirects** from all 22 legacy `lawmillion-*.html` URLs → clean URLs (in `.htaccess`)
- **Plus** a DB-driven `redirects` table so ops can add new redirects without editing config
- **Dynamic XML sitemap** at `/sitemap.xml`
- **`robots.txt`** explicitly allows GPTBot, ClaudeBot, PerplexityBot, ChatGPT-User, Google-Extended, anthropic-ai
- **`llms.txt`** for emerging AI-engine standard

---

## Migration from the old `.html` files

Your existing 22 HTML files are now obsolete. The `.htaccess` redirects each one to the new clean URL with a 301:

| Old URL | New URL |
|---|---|
| `/lawmillion-employment-law.html` | `/practice-areas/employment-law/` |
| `/lawmillion-personal-injury.html` | `/practice-areas/personal-injury/` |
| `/lawmillion-attorney-profile.html` | `/attorneys/tx/dallas/amanda-j-richardson/` |
| `/lawmillion-blog-list.html` | `/blog/` |
| `/lawmillion-blog-detail.html` | `/blog/tcja-sunset-2026-tax-cuts-jobs-act-guide/` |
| `/lawmillion-attorney-login.html` | `/for-attorneys/login/` |
| …all 22 files | …mapped 1:1 |

**The content** of each old HTML page (intro copy, FAQ accordion, body content) needs to be ported into the database. Open each HTML file, copy the relevant sections, and paste them into the corresponding columns:

- Practice area `intro_html`, `body_html`, `faq_json` → `practice_areas` table
- Blog body → `blog_posts.body_html`
- Attorney bio → `attorneys.bio_html`

Once the content is in the DB, the views will render it automatically with all SEO/schema markup.

---

## Go-live checklist

- [ ] Database schema + seed loaded
- [ ] All practice-area `intro_html` / `body_html` / `faq_json` populated from old HTML files
- [ ] Logo + favicon + OG default image uploaded to `public/assets/images/`
- [ ] HTTPS certificate installed (Let's Encrypt is free)
- [ ] DNS A record points to your server
- [ ] DocumentRoot set to `public/` directory
- [ ] `.env` file populated with real DB credentials (mode 600)
- [ ] `chmod 600 .env`
- [ ] Test in browser: homepage, one practice area, the attorney profile, the blog post
- [ ] Submit `https://lawmillion.com/sitemap.xml` to Google Search Console + Bing Webmaster Tools
- [ ] Verify 301 redirects from a few legacy `.html` URLs work
- [ ] Verify HTTPS forces (visit `http://` and confirm 301 to `https://`)
- [ ] Verify www→non-www redirect (visit `www.lawmillion.com` and confirm)
- [ ] Replace the placeholder password hash on the seeded sample attorney before allowing real logins (or delete that row)

---

## What's in scope vs. what's not

**This codebase delivers:**
- All 22 page types as PHP templates
- Database-driven content (you can edit copy without redeploying)
- Clean SEO URLs with 301s from legacy URLs
- Full schema.org JSON-LD on every page
- Lead capture form with validation + CSRF
- Attorney signup, login, dashboard with secure password hashing + sessions
- Dynamic sitemap, robots.txt, llms.txt

**Not in scope (extend as needed):**
- Email sending for new lead notifications (plug in PHPMailer + SendGrid/Postmark/SES)
- Stripe billing for paid attorney plans
- Admin panel for managing attorneys / verifying bar status
- File uploads (attorney photos)
- Full-text search across attorneys (consider Meilisearch or MySQL FULLTEXT for scale)
- Multi-language (intentional — US English only)

---

## Security notes

- All DB queries use prepared statements (`Database::pdo()`)
- All output escaped via `e()` (htmlspecialchars)
- CSRF tokens on every POST form (`csrf_token()` / `csrf_check()`)
- Passwords: bcrypt cost 12 (`PASSWORD_BCRYPT`)
- Sessions: HttpOnly, Secure (HTTPS), SameSite=Lax, regenerated on login
- Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS in production
- HTTPS forced + www→non-www at the front-controller level
- `.env`, `.git`, `composer.*`, `package.json` blocked at the Apache level
