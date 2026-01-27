<?php
return [
    "site_name" => "Domain Manager",
    "timezone" => "Europe/Paris",
    "alert_days" => [60, 30, 14, 7, 3, 1],
    "email_to" => "you@example.com",
    "email_from" => "domains@yourdomain.tld",
    "mail_subject_prefix" => "[Domain Alerts] ",
    "data_domains" => __DIR__ . "/data/domains.json",
    "data_notifications" => __DIR__ . "/data/notifications.json",
];
