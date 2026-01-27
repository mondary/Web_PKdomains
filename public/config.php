<?php
$config = [
    "site_name" => "Domain Manager",
    "timezone" => "Europe/Paris",
    "alert_days" => [60, 30, 14, 7, 3, 1],
    "language" => "fr",
    "email_to" => "",
    "email_from" => "",
    "mail_subject_prefix" => "",
    "db_path" => __DIR__ . "/../data/app.sqlite",
    "version" => "2.0.0",
    "default_username" => "admin",
    "default_password" => "admin123",
    "base_url" => "",
    "smtp_host" => "",
    "smtp_port" => 587,
    "smtp_user" => "",
    "smtp_pass" => "",
    "smtp_secure" => "starttls",
];

$cred = __DIR__ . "/../data/credentials.php";
if (is_file($cred)) {
    $extra = require $cred;
    if (is_array($extra)) {
        $config = array_merge($config, $extra);
    }
}

return $config;
