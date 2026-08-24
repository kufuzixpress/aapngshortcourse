# AAPNG Short Course Awards Database

Web application for managing Australia Awards PNG short course participants and their awards.

**Stack:** PHP 8+ · MySQL 5.7/8.0 · AdminLTE 4 · Bootstrap 5

## Structure

```
aapng-awards/
├── config/
│   ├── config.php          App settings (DB credentials, lockout rules, mail)
│   └── database.php        PDO connection
├── database/
│   └── schema.sql          Full schema + seed data (provinces, districts, lookups)
├── api/                    JSON endpoints (called by page JavaScript)
│   ├── participants.php    List / get / save (dup-check, AAPNG ID gen) / delete
│   ├── awards.php          List / get / save (code generation) / delete
│   ├── users.php           Admin: list / save / unlock / activate / delete
│   ├── lookups.php         Dropdown options + admin lookup management
│   └── districts.php       Districts filtered by province (cascading dropdown)
├── includes/
│   ├── functions.php       Sessions, CSRF, flash messages, helpers
│   ├── auth.php            Login/lockout logic, role guards
│   ├── api.php             API bootstrap (auth + CSRF for endpoints)
│   ├── header.php          Page header + navbar (all pages)
│   ├── sidebar.php         Role-aware sidebar menu
│   └── footer.php          Page footer + script includes
├── assets/
│   ├── css/brand.css       Branding colours — edit to match your other systems
│   └── js/app.js           Shared front-end helpers (API, toasts, forms)
├── setup/create-admin.php  One-time first-admin creation (delete after use)
├── login.php / logout.php
├── forgot-password.php / reset-password.php
├── change-password.php
├── index.php               Dashboard
├── participants.php        Personal details — list + add/edit/view popups
├── awards.php              Awards — list + add/edit/view popups
├── users.php               User management (admin) — popups
└── settings.php            System settings (admin) — lookup manager popup
```

## Installation

1. Copy the `aapng-awards` folder into your web root (e.g. `htdocs/` or `/var/www/html/`).
2. Import the database: `mysql -u root -p < database/schema.sql`
   (or run `database/schema.sql` in phpMyAdmin).
3. Edit `config/config.php` — set DB credentials and `APP_URL`.
4. Browse to `APP_URL/setup/create-admin.php`, create the first admin account,
   then **delete the `setup/` folder**.
5. Log in at `APP_URL/login.php`.

## Branding

Edit the CSS variables at the top of `assets/css/brand.css`:

```css
--brand-primary:   #1b2f5e;   /* replace with your organisation's primary colour */
--brand-secondary: #c8a24a;   /* accent colour */
```

## Security features

- Passwords hashed with `password_hash()` (bcrypt)
- Account locks for 30 minutes after **3 failed login attempts** (admins can unlock)
- CSRF tokens on all forms
- Password reset via time-limited single-use email tokens (60 min)
- Session ID regeneration on login; HTTP-only session cookies
- Role-based access: `admin` (full) and `staff` (records only, no settings)

## Mail configuration

Password reset uses PHP `mail()`. On a server without sendmail, configure SMTP in
`php.ini` or swap in PHPMailer. During development, `MAIL_DEBUG = true` in
`config/config.php` displays the reset link on screen instead of emailing.

## Roles

| Capability | Admin | Staff |
|---|---|---|
| Dashboard, search | ✔ | ✔ |
| Personal details (add/edit/view) | ✔ | ✔ |
| Awards (add/edit/view) | ✔ | ✔ |
| System settings / lookup tables | ✔ | ✘ |
| User management | ✔ | ✘ |
