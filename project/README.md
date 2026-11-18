# Slotify Lite

Tiny booking system in plain PHP 8.1+ (PDO + SQLite). No Composer, no framework.

## Run
    cd slotify-lite
    php -S localhost:8000 -t public
Open http://localhost:8000 — admin at `?p=admin/login` (password in `config.php`, default `change-me`).

Requires the `pdo_sqlite` extension. Make `data/` writable by the web server.
On Apache/Nginx, point the document root at `public/` so `data/` and `config.php` are never web-exposed.

## How double bookings are prevented
1. Booking runs in a `BEGIN IMMEDIATE` transaction (takes the write lock before checking).
2. The database itself enforces it: `UNIQUE INDEX ... ON bookings(slot_id) WHERE status='confirmed'`.
   Even if app logic were bypassed, a second confirmed booking for a slot is rejected.
3. Cancelling sets `status='cancelled'`, which frees the slot automatically.

## Layout
    config.php  schema.sql  src/bootstrap.php  public/{index.php,style.css}  views/*.php

## Flow
- Customers: pick slot → form → private confirmation link (`?p=done&t=TOKEN`) used to cancel.
- Admin: generate slots by date/hours/length, see bookings, cancel any booking, delete free slots.
