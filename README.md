# MUSA Beauty Management System

A web-based Beauty Management System that enables clients to book appointments, stylists to manage availability, and administrators to manage salon operations.

## Features

- **User Authentication**: Secure login/registration for clients, stylists, and admins
- **Appointment Booking**: Clients can browse services, select stylists, and book appointments
- **Service Management**: Admin can add/edit/delete services with categories, pricing, and descriptions
- **Stylist Management**: Admins manage stylist profiles and specializations
- **Client Dashboard**: View upcoming bookings, book new appointments, leave reviews
- **Stylist Dashboard**: Manage schedule and view assigned appointments
- **Admin Dashboard**: Oversee users, services, appointments, and generate reports
- **Smart Recommendations**: Personalized stylist suggestions based on booking history and ratings
- **Review System**: Clients can rate and review stylists after completed appointments
- **Responsive Design**: Mobile-friendly interface with modern UI

## Project Structure

- `assets/css/style.css` — shared responsive styles
- `assets/js/main.js` — UI interactions and date validation
- `config/db.php` — secure PDO database connection
- `includes/header.php` / `footer.php` / `auth.php` — shared layout and authentication helpers
- `client/` — client dashboards, booking, appointments, reviews
- `stylist/` — stylist dashboards, schedule and appointment management
- `admin/` — admin dashboard, user and service management, reports
- `index.php` — landing page with services and stylist highlights
- `services.php` — service listing with category filtering and booking links
- `login.php`, `register.php`, `logout.php` — auth workflow
- `db_schema.sql` — MySQL schema and seed data

## Setup

1. Install MySQL server:
   ```bash
   sudo apt-get update && sudo apt-get install -y mysql-server
   sudo service mysql start
   ```

2. Create database and import schema:
   ```bash
   sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS musa_beauty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   sudo mysql -u root -e "CREATE USER IF NOT EXISTS 'musa_user'@'localhost' IDENTIFIED BY 'musa_pass';"
   sudo mysql -u root -e "GRANT ALL PRIVILEGES ON musa_beauty.* TO 'musa_user'@'localhost'; FLUSH PRIVILEGES;"
   sudo mysql -u root musa_beauty < db_schema.sql
   ```

3. Place the project in a PHP-enabled web server root.
4. Or run the app locally using npm:
   ```bash
   npm start
   ```
   This launches the PHP built-in server on `0.0.0.0:8000` using `/usr/bin/php8.3`, which includes PDO MySQL support.
5. Open the app in your browser at `http://localhost:8000` or use the forwarded port.
6. Kill the port from the terminal using 'npx kill-port 8000'

## MPesa / Daraja Integration

You can now offer MPesa STK Push at appointment booking time.

1. Create a Daraja app and get your credentials from Safaricom.
2. Add them to your environment (or `.env`):
   ```bash
   MPESA_ENVIRONMENT=sandbox
   MPESA_CONSUMER_KEY=your_consumer_key
   MPESA_CONSUMER_SECRET=your_consumer_secret
   MPESA_SHORTCODE=174379
   MPESA_PASSKEY=your_passkey
   MPESA_CALLBACK_URL=http://localhost:8000/client/mpesa_callback.php
   ```
3. Restart the server and use the booking page's "MPesa (Daraja STK Push)" option.

The booking flow uses the Daraja STK Push API and stores callback data in `mpesa_payments` for confirmation. The callback handler also updates the related appointment record to `paid` or `failed` based on `ResultCode`.

## SMS Notifications

- Phone numbers are stored in the `users` table and are required during registration.
- The reusable helper is in `includes/sms.php` with a fallback logger at `logs/sms.log`.
- Appointment confirmation and cancellation messages are sent automatically when clients book or cancel appointments.
- Run the reminder script daily to send 24-hour reminders:
  ```bash
  php send_appointment_reminders.php
  ```

## Default credentials

- Admin: `admin@example.com` / `admin123`

## Notes

- Passwords are hashed with `password_hash()`.
- Prepared statements protect SQL queries.
- Appointment booking checks for stylist double-booking.
- Client, stylist, and admin roles are supported with dedicated dashboards.

## Environment & Running (DB-safe defaults)

This project prefers environment variables for database credentials. Copy `.env.example` to `.env` and update values when deploying.

Example `.env` values:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=musa_beauty
DB_USER=musa_user
DB_PASS=musa_pass
```

Health check:

```
php8.3 health-check.php
```

Or open `http://localhost:8000/health-check.php` when the dev server is running.

Start local development server:

```
sudo service mysql start

npm start
```

This uses `php8.3` as the runtime to ensure PDO MySQL support.
