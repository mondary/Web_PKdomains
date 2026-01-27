# Domain Manager (PHP, minimal)

Minimal web dashboard for tracking domain expirations with email alerts.

## Files
- `index.php`: dashboard UI
- `alert.php`: email alert script (run by cron)
- `config.php`: settings
- `data/domains.json`: your domains
- `data/notifications.json`: alert log (auto)
- `assets/`: CSS/JS

## Setup
1) Edit `config.php` (timezone, email_to/from, thresholds).
2) Update `data/domains.json` with your domains.
3) Upload everything to your OVH FTP.
4) Open `index.php` in browser.

## Email alerts (cron)
Run daily:
```
php /path/to/alert.php
```

On OVH, add a cron task in the manager (daily at e.g. 08:00).

## Notes
- Uses PHP `mail()`. If your host blocks it, you'll need SMTP (I can add that).
- All data is JSON for simplicity.
