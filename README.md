# Domain Manager (PHP, minimal)

Minimal web dashboard for tracking domain expirations with email alerts.
Now uses SQLite for authentication and data.

## Files
- `index.php`: main entry (root)
- `public/`: web assets + app entrypoints
- `public/app/`: application logic (lib + cron)
- `public/config.php`: configuration (tracked)
- `data/app.sqlite`: database (users, domains, notifications)
- `data/secrets/credentials.php`: secrets (ignored by git)

## Setup
1) Edit `public/config.php` (timezone, DB path, etc.).
2) Create `data/secrets/credentials.php` (see below).
3) Upload everything to your OVH FTP.
4) Ensure `data/` is writable by PHP.
5) Open the site in browser.

## Credentials (required)
Create `data/secrets/credentials.php` with at least these keys:
```php
<?php
return [
  "email_from" => "domains@yourdomain.tld",
  "smtp_host" => "smtp.mail.ovh.net",
  "smtp_port" => 587,
  "smtp_user" => "domains@yourdomain.tld",
  "smtp_pass" => "YOUR_PASSWORD",
  "smtp_secure" => "starttls",
  "default_username" => "admin",
  "default_password" => "admin123",
];
```

Optional keys you can add:
```php
"mail_subject_prefix" => "[Domain Alerts] ",
```

`email_to` is per-user (set in Options in the UI).

## RDAP auto-fill (free)
The add form can auto-fill registrar and expiration using public RDAP servers.
This is free but can be rate-limited or incomplete depending on the TLD.

## Email alerts (cron)
Run daily:
```
php /path/to/public/app/cron/alert.php
```

On OVH, add a cron task in the manager (daily at e.g. 08:00).

## Notes
- Uses SMTP if configured in `data/credentials.php`, otherwise falls back to PHP `mail()`.
- All data is stored in SQLite.
