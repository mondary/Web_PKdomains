<?php
// Optional secrets override (not committed).
$secrets = [];
if (is_file(__DIR__ . "/secrets/creds.php")) {
    $secrets = require __DIR__ . "/secrets/creds.php";
}

return [
    "site_name" => "Domain Manager",
    "timezone" => "Europe/Paris",
    "alert_days" => [60, 30, 14, 7, 3, 1],
    "email_to" => $secrets["email_to"] ?? "you@example.com",
    "email_from" => $secrets["email_from"] ?? "domains@yourdomain.tld",
    "mail_subject_prefix" => "[Domain Alerts] ",
    "db_path" => __DIR__ . "/data/app.sqlite",
    "default_username" => $secrets["default_username"] ?? "admin",
    "default_password" => $secrets["default_password"] ?? "admin123",
];
