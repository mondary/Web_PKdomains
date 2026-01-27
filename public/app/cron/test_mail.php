<?php
$config = require __DIR__ . "/../../config.php";
require_once __DIR__ . "/../lib/db.php";
date_default_timezone_set($config["timezone"]);

function send_test_mail(string $to, string $from, string $subject, string $message): bool {
    $headers = "From: {$from}\r\nReply-To: {$from}\r\n";
    return mail($to, $subject, $message, $headers);
}

$to = $config["email_to"] ?? "";
$from = $config["email_from"] ?? "";
if ($to === "" || $from === "") {
    echo "Missing email_to or email_from in config.\n";
    exit(1);
}

$subject = ($config["mail_subject_prefix"] ?? "") . "Test notification";
$message = "This is a test notification from Domain Manager.\n";
$message .= "Time: " . (new DateTime())->format("Y-m-d H:i:s") . "\n";

$ok = send_test_mail($to, $from, $subject, $message);

echo $ok ? "Test mail sent to {$to}.\n" : "Test mail failed.\n";
exit($ok ? 0 : 2);
