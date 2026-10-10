# SME Financial Tracker — XAMPP / PHP / MySQL

This version replaces the earlier Python/SQLite backend with **PHP + PDO MySQL**. Use **phpMyAdmin** to import and inspect the database. The application connects directly to MySQL/MariaDB; phpMyAdmin is the database management interface.

The interface uses warm backgrounds, muted sage accents, readable text, a fixed desktop sidebar, and responsive mobile navigation. Application styles are maintained in static/workspace.css.

## XAMPP setup (Windows)

1. Extract the ZIP and copy the **SME** folder into:
   `C:\xampp\htdocs\`
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Open **http://localhost/phpmyadmin**.
4. From the phpMyAdmin home page, click **Import**.
5. Choose `SME/database/sme_financial_tracker.sql` and click **Go**.
   The file creates `sme_financial_tracker`, four tables, categories, demo users and sample records.
6. Open **http://localhost/SME/**.
7. Sign in with one of the accounts below.

Open `index.html` through http://localhost/SME/ rather than double-clicking it. The PHP API must run through Apache. Python and Node.js are not needed to run the application.

On Linux/macOS, copy the folder into your XAMPP installation's `htdocs` directory and follow the same import and localhost steps. If Apache runs on a different port, include it in both URLs, such as `http://localhost:8080/SME/`.

## Database configuration

Edit `config.php` if your database credentials or port differ:

```php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'sme_financial_tracker',
    'username' => 'root',
    'password' => '',
    'secure_cookie' => false,
];
```

These are local XAMPP defaults. Requires 64-bit PHP 7.4+ with `pdo_mysql` enabled, Apache 2.4+, and MySQL/MariaDB. No Composer installation or rewrite rules are needed. The query-string API works from a subfolder.

**Import once into a fresh database.** The SQL file does not delete existing data. Reimporting into an initialized database causes duplicate-key errors for seed records; it is not a reset script. If the database already contains your records, keep it and skip the import. Export backups in phpMyAdmin before making database changes.

## Demo accounts

| Email | Role | Permissions |
|---|---|---|
| owner@sme.local | Business Owner | Create, view, edit and delete financial records; reports and CSV export |
| accountant@sme.local | Accountant | View records and reports, export and mark records reviewed |
| admin@sme.local | Admin | All record operations, reviews, account creation and activity log |

Default password for all three: **SmeDemo2026!**

The imported password hashes use bcrypt. New accounts use PHP `password_hash()` and logins use `password_verify()`. All accounts share one business workspace. The seven imported financial records are sample data; delete them through the app before real use.

## Features and assignment requirements

| Requirement | Implementation |
|---|---|
| Login / Logout | PHP sessions, password verification, session ID regeneration, expiry and logout |
| Protected pages | Login required to load workspace data; every protected API call validates the session |
| Dashboard, at least 3 database-derived values | Net balance, total income, total expenses and pending review |
| Create / Read / Update / Delete | Form entry, joined record table, edit form and confirmed deletion |
| SQL CRUD | PDO prepared SELECT, INSERT, UPDATE and DELETE queries |
| SQL JOIN | Transactions joined with categories and creator accounts; activity joined with users |
| Client-side validation | HTML constraints plus immediate JavaScript validation for descriptions, amounts and dates |
| Server-side validation | Amount precision/range, valid date, description length, category/type and role checks |
| Database integration | InnoDB tables, foreign keys, MySQL transaction commits and rollbacks |
| Search / Filter | Keyword, review status, date range and sorting |
| Responsive UI | Desktop sidebar and mobile horizontal navigation |
| Bonus features | Role access, pagination, CSV export and activity logging |

Accountants review records without editing or deleting them. Owners create and update entries. Admins can manage entries, review them and create accounts. Editing a reviewed record sends it back to pending review.

Reports cover basic income, expenses, net result, net margin, monthly cash flow and category breakdown. They include pending and reviewed entries unless filtered. Amounts are stored as integer cents to avoid floating-point storage errors. Activity timestamps are stored and displayed in UTC; record dates are calendar dates.

**Out of scope:** full accounting, tax filing, bank feeds, multiple business workspaces, password reset and user deactivation screens.

## Files

- `index.html` — application shell, login and dialogs.
- `api.php` — PHP authentication, role checks, server validation, SQL CRUD and joins.
- `config.php` — database connection settings.
- `database/sme_financial_tracker.sql` — phpMyAdmin import, schema and demo data.
- `static/app.js` — screens, interactions, reports, charts and CSV export.
- `static/style.css` — financial tracker and responsive styles.
- `static/grc-app.css`, `static/grc-theme.css` — retained GRC reference styles.
- `test_api.py` — optional HTTP integration test to run against your local XAMPP instance.

## Verification

JavaScript syntax, frontend references, packaging and demo password hashes were checked during creation. **PHP/MySQL and visual browser tests could not be run in the creation environment.** The Python version's earlier test results do not verify this PHP backend.

After starting XAMPP, optionally run:

```text
python test_api.py
```

Or specify another port/path:

```text
python test_api.py --url http://localhost:8080/SME/
```

Python is needed only for this optional test script. It exercises login, unauthorized access, CSRF, server validation, role enforcement, JOIN-derived labels, create/read/update/delete, review reset and logout. It creates a temporary financial record, removes it afterward, and leaves the corresponding audit entries. Run against a development database.

Manual checks: sign in as owner; add, search, edit and delete an entry; verify dashboard totals and CSV exports. Sign in as accountant and review an entry. Sign in as admin and create a user. Resize the browser to check the mobile layout.

## Troubleshooting

- **Database unavailable:** start MySQL, import the SQL file, then check database name, password and port in `config.php`.
- **Blank page or PHP source displayed:** start Apache and use the localhost URL; check PHP is enabled in Apache.
- **Could not find driver:** enable `extension=pdo_mysql` in XAMPP's PHP configuration and restart Apache.
- **Access forbidden:** place the complete extracted folder under `htdocs`; check Apache permissions.
- **Port already in use:** resolve the port conflict in XAMPP or use your configured Apache/MySQL ports.
- **Login fails:** verify the SQL import includes all three users and use the exact demo password.

This package is configured for local XAMPP use. Before internet deployment, use HTTPS and secure cookies, a dedicated restricted database account, private passwords, database backups and stronger login rate limiting. The `.htaccess` files deny web access to the configuration and SQL directory.
