# Domain Manager (PHP, minimal)

Minimal web dashboard for tracking domain expirations with email alerts.
Now uses SQLite for authentication and data.

## Files
- `public/`: web root (PHP + assets + app)
- `public/app/`: application logic (lib + cron)
- `config.php`: configuration (tracked)
- `data/app.sqlite`: database (users, domains, notifications)

## Setup
1) Edit `config.php` (timezone, email_to/from, thresholds, DB path).
2) Upload everything to your OVH FTP.
3) Point your web root to `public/`.
4) Ensure `data/` is writable by PHP.
5) Open the site in browser.

Default login (change after first login):
```
username: admin
password: admin123
```

No secrets override file is used.

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
- Uses PHP `mail()`. If your host blocks it, you'll need SMTP (I can add that).
- All data is stored in SQLite.
