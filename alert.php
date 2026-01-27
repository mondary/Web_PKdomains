<?php
$config = require __DIR__ . "/config.php";
date_default_timezone_set($config["timezone"]);

$domains = [];
if (is_file($config["data_domains"])) {
    $domains = json_decode(file_get_contents($config["data_domains"]), true) ?: [];
}

$notifications = [];
if (is_file($config["data_notifications"])) {
    $notifications = json_decode(file_get_contents($config["data_notifications"]), true) ?: [];
}

function days_until($date) {
    $today = new DateTime("today");
    $exp = DateTime::createFromFormat("Y-m-d", $date);
    if (!$exp) {
        return null;
    }
    $diff = $today->diff($exp);
    return (int)$diff->format("%r%a");
}

function send_alert($to, $from, $subject, $message) {
    $headers = "From: {$from}\r\nReply-To: {$from}\r\n";
    return mail($to, $subject, $message, $headers);
}

$today = (new DateTime("today"))->format("Y-m-d");
$sent = 0;

foreach ($domains as $d) {
    $domain = $d["domain"] ?? "";
    $expires = $d["expires"] ?? "";
    $days = days_until($expires);
    if ($days === null) {
        continue;
    }

    if (!in_array($days, $config["alert_days"], true)) {
        continue;
    }

    $key = $domain . ":" . $days;
    $already = $notifications[$key]["date"] ?? null;
    if ($already === $today) {
        continue;
    }

    $to = $d["email"] ?? $config["email_to"];
    $subject = $config["mail_subject_prefix"] . $domain . " expires in " . $days . " day(s)";
    $message = "Domain: {$domain}\nExpires: {$expires}\nDays left: {$days}\n";

    if (send_alert($to, $config["email_from"], $subject, $message)) {
        $notifications[$key] = ["date" => $today];
        $sent++;
    }
}

file_put_contents($config["data_notifications"], json_encode($notifications, JSON_PRETTY_PRINT));
echo "Alerts sent: {$sent}\n";
