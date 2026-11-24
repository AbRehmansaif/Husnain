# DonorNexa

**Connecting donors. Bridging lives. Saving hope.**

DonorNexa is a local-first, role-based emergency blood donor coordination portal built with PHP, MySQL, semantic HTML, hand-written CSS and vanilla JavaScript. It is designed to run on XAMPP on another Windows PC without Composer, Node.js, a framework install or an internet connection.

## What is included

- Public landing page, live AJAX community statistics and a privacy-safe active emergency board.
- Donor sign-up, profile editing and photo upload, availability controls, eligibility date, matched request responses, donation history and milestone badges.
- Hospital sign-up with required PDF licence upload and pending verification; verified hospitals can create requests, find ranked matches, see donor phone numbers only after acceptance, confirm donations and manage inventory.
- Admin tools for hospital verification, account blocking, city management, configurable eligibility cooldown/match size, CSV reports, weekly/demand charts and a searchable audit log.
- A MySQL schema with foreign keys, checks, indexes, a view, a trigger, constraints and seed data for 30 donors, 5 hospitals and 20 requests.
- An implementation/concept map and project report in [`docs/project-report.md`](docs/project-report.md).

## Run locally with XAMPP (Windows)

1. Install XAMPP with **PHP 8.1+**, Apache and MySQL/MariaDB. Make sure `pdo_mysql`, `fileinfo` and `mbstring` are enabled in `php.ini`.
2. Copy the complete `DonorNexa` folder to `C:\xampp\htdocs\DonorNexa`.
3. Start Apache and MySQL from the XAMPP Control Panel.
4. Open phpMyAdmin at `http://localhost/phpmyadmin`.
5. Import [`sql/schema.sql`](sql/schema.sql) first, then [`sql/seed.sql`](sql/seed.sql). The schema creates and selects the `donornexa` database.
6. If your local MySQL credentials are not the XAMPP defaults, copy `config\local.example.php` to `config\local.php` and set your database host, name, username and password there. `config\local.php` is ignored by Git. Environment variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASSWORD` override the local file.
7. Browse to `http://localhost/DonorNexa/public/`.

The app's public document root is the `public` folder. This keeps the PHP source, SQL files, configuration and logs outside the web root. No URL rewrite module is required; the front controller uses query-string routes.

### Demo accounts

Every seeded account uses the local-only password **`password`**:

| Role | Email |
| --- | --- |
| Administrator | `admin@donornexa.test` |
| Eligible Lahore donor for the demo flow (A+) | `ali.raza@donornexa.test` |
| Verified hospital | `fmh@donornexa.test` |

These are demo credentials, not production credentials. Replace demo accounts and passwords before any non-local use. The seed includes fictional people and fictional records only.

### Quick end-to-end donor flow

1. Sign in as `fmh@donornexa.test` and create a request for **A+**, one unit, in Lahore, with a future required-by time.
2. Sign out, then sign in as `ali.raza@donornexa.test`. This seeded donor is A+, available, and eligible; the new request should appear under **Requests matched for you** with an **I can help** response.
3. After the donor accepts, sign back in as the hospital and open that request's **View matches** page. The donor's contact is revealed only after acceptance. Use **Mark complete** only to simulate a completed donation; it updates inventory and starts the donor cooldown.

The seeded Hamza account has a recent donation and is ineligible during the cooldown. Also, an O+ donor is not compatible with an O− request. Those cases correctly have no **I can help** action. Hospital requests are saved in **Recent requests** and matching runs when each request is published.

### Useful URLs

- Public landing page: `http://localhost/DonorNexa/public/`
- Public emergency board: `http://localhost/DonorNexa/public/index.php?page=emergencies`
- Admin analytics: sign in as the administrator and open the dashboard.
- Application error log: `DonorNexa\logs\error.log` (created automatically).
- Donor photos and hospital licence PDFs are stored outside the web root in `DonorNexa\storage\` and streamed only to their authorized viewer.

## Database and local configuration

The included defaults match a typical fresh XAMPP setup (`127.0.0.1:3306`, database `donornexa`, user `root`, empty password). For another machine, configure `config/local.php`; do not commit real credentials. Import a fresh schema before seed data. `seed.sql` is a demo dataset, not a production migration.

The app uses the `Asia/Karachi` timezone. MySQL should have InnoDB enabled. MySQL 8.0.16+ or a recent MariaDB version is recommended for enforced `CHECK` constraints and JSON columns.

## Main project structure

```text
DonorNexa/
├── api/                 JSON endpoint dispatcher
├── config/              App, PDO connection and local config example
├── docs/                CEP report and ERD
├── logs/                 Runtime error log (created as needed)
├── public/               Apache document root, front controller and assets
│   ├── assets/css/        Responsive, hand-written CSS
│   ├── assets/js/         Fetch/AJAX, validation and countdown behavior
├── sql/                  MySQL schema, view, trigger and demo seed
├── src/                  Namespaced controllers, models, repositories and services
├── storage/              Private donor photos and hospital licence uploads
└── templates/            Layout and role-specific pages
```

## Important implementation notes

- The ABO/Rh red-cell compatibility map is data-driven in `CompatibilityService`.
- Eligibility uses age 18–60, weight at least 50 kg, active donor account, availability and the admin-configured last-donation cooldown.
- Donor matching ranks compatible eligible donors by exact blood-group match, city, capped days since donation and response rate. Critical requests expand the initial notification pool.
- Donor acceptance and hospital completion lock the request row in a PDO transaction. `units_reserved` accounts for accepted donations separately from `units_fulfilled`.
- Public emergency responses contain only city, blood group, urgency, unit counts and deadline. Ward notes and phone numbers are never public.
- Contact numbers are returned to a hospital only for an accepted/donated match, and each reveal is audited.
- Donor photos are MIME-checked, renamed, capped at 2 MB and served only to their owner; licence PDFs are MIME-checked, renamed, capped at 5 MB and served only to an administrator.
- The optional “keep me signed in” cookie is random, HttpOnly, SameSite=Lax, Secure under HTTPS, stored as a hash and rotated on restoration.

## Known demo limitations

- This is a coursework/local demonstration, not a clinical triage or blood-bank inventory system. Hospitals must validate medical suitability and all donation records.
- `RequestExpiryService` runs at each web request rather than from a background scheduler. Production should use a cron/Task Scheduler job.
- A donor's matched request can generate notifications in the portal; SMS, WhatsApp and email integrations are intentionally not configured.
- The CSV export is an administrative report and does not replace a validated operational integration.
- Add HTTPS, backups, rate limiting, operational monitoring, real licence review and formal security/privacy review before any real deployment.

## Academic integrity and report

The CEP brief says AI-generated code must be declared and understood. This project was AI-assisted; the student team should review the code, test it locally, complete the report's screenshot checklist, and be ready to explain the compatibility map, eligibility query, transaction/locking approach, privacy boundary and trade-offs during the viva.


To test a complete flow, use this compatible demo pair:

Sign out of the hospital account. Sign in as ali.raza@donornexa.test with password password. Ali is an available, eligible A+ donor in Lahore.
Sign out, then sign in as fmh@donornexa.test / password.
Create a new A+ request for 1 unit with a future deadline. Publish it; the hospital’s Recent requests list should show it.
Sign out and sign in as Ali. The request should appear under Requests matched for you with the I can help option.
After Ali accepts, sign back in as FMH and open that request’s View matches page. The donor’s phone is revealed after acceptance. Use Mark complete only to simulate a completed donation.