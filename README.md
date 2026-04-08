# PrivateHire (Agile Scrum Coursework)

PrivateHire is a PHP + MySQL taxi booking platform built across 3 Scrum sprints.
This project runs locally with XAMPP.

## Tech Stack
- PHP 8.x
- MariaDB/MySQL (via XAMPP)
- Apache (via XAMPP)
- PHPMailer (`phpmailer/phpmailer`)
- Bootstrap 5 (CDN)

## 1) Prerequisites
- Install XAMPP
- Ensure these folders exist:
  - Project code: `C:\xampp\htdocs\privatehire`
  - Apache + MySQL services available in XAMPP Control Panel

## 2) Start Services
1. Open **XAMPP Control Panel**
2. Start:
   - `Apache`
   - `MySQL`

## 3) Database Setup
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create database:
   - Name: `privatehire`
   - Collation: `utf8mb4_general_ci` (optional)
3. Import SQL:
   - Select `privatehire` database
   - Go to **Import**
   - Choose file: [`privatehire.sql`](./privatehire.sql)
   - Click **Go**

### Notes
- The app also runs schema upgrades automatically on startup via [`config/db.php`](./config/db.php).
- Importing `privatehire.sql` first is still recommended.

## 4) Install PHP Dependencies
From project root:

```powershell
cd C:\xampp\htdocs\privatehire
composer install
```

If `composer` is not on PATH, use `composer.phar` or install Composer globally.

## 5) Configure App
### Database
Default DB connection is in [`config/db.php`](./config/db.php):
- host: `localhost`
- user: `root`
- password: ``
- database: `privatehire`

If your local MySQL password differs, update this file.

### Email (PHPMailer)
Mail helper is in [`config/services.php`](./config/services.php).

Recommended: set environment variables for SMTP:
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_APP_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

Without env vars, fallback values are used (for coursework/demo behavior).

## 6) Run the App
Open:

```text
http://localhost/privatehire/
```

## 7) Key Routes
### Customer
- Home: `/privatehire/`
- Register: `/privatehire/auth/register.php`
- Login: `/privatehire/auth/login.php`
- Book Ride: `/privatehire/booking/book.php`
- Booking History: `/privatehire/booking/my_bookings.php`

### Admin
- Dashboard: `/privatehire/admin/dashboard.php`
- Drivers CRUD: `/privatehire/admin/drivers.php`
- Vehicles CRUD: `/privatehire/admin/vehicles.php`
- Call Centre: `/privatehire/admin/call_bookings.php`
- Reviews: `/privatehire/admin/reviews.php`
- Reports & Loyalty: `/privatehire/admin/reports.php`
- Enquiries: `/privatehire/admin/enquiries.php`
- GPS Dashboard (simulated): `/privatehire/admin/gps_dashboard.php`

## 8) Background Scripts (Run Manually or Schedule)
From project root:

### Booking reminders (10–15 mins before pickup)
```powershell
php booking\send_reminders.php
```

### Delay notifications (ETA > 10 mins simulation)
```powershell
php booking\check_delays.php
```

### Suggested scheduling (Windows Task Scheduler)
- Run every 5 minutes:
  - `php C:\xampp\htdocs\privatehire\booking\send_reminders.php`
  - `php C:\xampp\htdocs\privatehire\booking\check_delays.php`

## 9) Common Troubleshooting
### `Access denied` to DB
- Confirm MySQL is running
- Confirm DB creds in [`config/db.php`](./config/db.php)

### `Class PHPMailer not found`
- Run `composer install`
- Ensure `vendor` folder exists

### No relationship lines in phpMyAdmin Designer
- Foreign keys must exist in DB
- Ensure tables are InnoDB
- Refresh Designer after adding constraints

### Email not sending
- Check SMTP credentials/environment variables
- Check PHP error log / Apache error log

## 10) Coursework Notes
- Payment gateway and GPS integrations are implemented as **coursework simulation stubs** for end-to-end flow demonstration.
- GPS dashboard currently uses hardcoded simulated fleet data for UI/interaction validation.

