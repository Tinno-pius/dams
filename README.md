# DAMS — Digital Antenatal Monitoring System

A web-based (and mobile-responsive) system for monitoring antenatal (ANC) care,
built as a final-year Computer Engineering project.

It digitises the Tanzanian **RCH 4** clinic card (*Kadi ya Kliniki ya Waja Wazito*)
and lets a clinic register pregnant women, record their ANC visits, track danger
signs, schedule appointments, send SMS reminders and view reports.

## Development Tools

| Layer | Technology |
|-------|------------|
| Front-end | HTML5, CSS3, **Bootstrap 5**, JavaScript |
| Back-end  | **PHP 8** |
| Database  | **MySQL** (`dams_db`) |
| Local server | **XAMPP** (Apache + MySQL) |
| Charts    | Chart.js |
| SMS       | Africa's Talking API (or Beem SMS) |

## Users (roles)

1. **Administrator** — manages users/nurses, health articles, reports, settings, database backup.
2. **Health Worker (Nurse)** — registers patients, fills the RCH4 card, records ANC visits & labs, books appointments.
3. **Pregnant Woman (Patient)** — views her profile, RCH4 card, ANC history, appointments, health education and notifications.

## How to run on XAMPP

1. Install and open **XAMPP**, then start **Apache** and **MySQL**.
2. Copy this whole `dams` folder into `xampp/htdocs/` so the path is `xampp/htdocs/dams`.
3. Open **phpMyAdmin**: <http://localhost/phpmyadmin>
4. Create a database named **`dams_db`**, then **Import** the file
   [`database/dams_db.sql`](database/dams_db.sql). This creates all the tables
   and a few demo accounts.
5. Open the database settings in [`config/config.php`](config/config.php).
   The defaults match a normal XAMPP install (host `127.0.0.1`, user `root`, empty password).
6. Visit the app: <http://localhost/dams>

### Demo accounts (password: `password123`)

| Role | Email |
|------|-------|
| Administrator | `admin@dams.com` |
| Health Worker | `healthworker@clinic.com` |
| Patient | `patient@gmail.com` |

## SMS reminders

Open [`config/config.php`](config/config.php) and set your Africa's Talking or Beem
API keys. Until keys are added, messages are **simulated** and still written to the
SMS log so the rest of the system works during development.

To send appointment reminders (1 day and 3 days before), run the script daily:

```
php modules/sms/send_reminders.php
```

## Folder structure

```
dams/
├── admin/          Administrator pages
├── healthworker/   Nurse pages
├── patient/        Patient pages
├── assets/         css, js, images, fonts
├── config/         config.php, database.php
├── database/       dams_db.sql
├── includes/       init, auth, header, sidebar, footer, helpers, csrf, language
├── language/       en.php, sw.php  (English / Kiswahili)
├── modules/
│   ├── rch4/       Digital RCH4 card (edit + view/print)
│   ├── reports/    Reports & analytics
│   ├── appointments/
│   └── sms/        SMS service + reminders
├── uploads/
├── index.php  login.php  logout.php
└── README.md
```

## Security features

- Passwords stored with `password_hash()` / `password_verify()`.
- All database access uses **PDO prepared statements** (protects against SQL injection).
- **Role-based access control** on every page (`require_role`).
- **CSRF tokens** on every form that changes data.
- Output escaped with `htmlspecialchars()` (protects against XSS).
- Secure, HttpOnly session cookies and session-id regeneration on login.

## Language

The interface can switch between **English** and **Kiswahili** using the language
toggle in the top navigation bar.
