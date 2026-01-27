<?php
$config = require __DIR__ . "/../../config.php";
require_once __DIR__ . "/../lib/db.php";
date_default_timezone_set($config["timezone"]);

$db = get_db($config);
$domains = $db->query("SELECT id, domain, expires, email FROM domains")->fetchAll();

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

    $stmt = $db->prepare("SELECT id FROM notifications WHERE domain_id = :id AND days = :days AND date = :date");
    $stmt->execute([":id" => $d["id"], ":days" => $days, ":date" => $today]);
    if ($stmt->fetchColumn()) {
        continue;
    }

    $to = ($d["email"] ?? "") ?: $config["email_to"];
    $subject = $config["mail_subject_prefix"] . $domain . " expires in " . $days . " day(s)";
    $message = "Domain: {$domain}\nExpires: {$expires}\nDays left: {$days}\n";

    if (send_alert($to, $config["email_from"], $subject, $message)) {
        $ins = $db->prepare("INSERT OR IGNORE INTO notifications (domain_id, days, date) VALUES (:id, :days, :date)");
        $ins->execute([":id" => $d["id"], ":days" => $days, ":date" => $today]);
        $sent++;
    }
}

echo "Alerts sent: {$sent}\n";
