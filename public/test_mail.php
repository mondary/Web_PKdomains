<?php
$config = require __DIR__ . "/config.php";
require_once __DIR__ . "/app/lib/db.php";
require_once __DIR__ . "/app/lib/auth.php";
require_once __DIR__ . "/app/lib/i18n.php";
require_once __DIR__ . "/app/lib/url.php";
require_once __DIR__ . "/app/lib/mail.php";

header("Content-Type: application/json; charset=utf-8");

require_login($config);

$db = get_db($config);
$uid = (int)($_SESSION["user_id"] ?? 0);
$config = apply_settings($db, $config, $uid);
$GLOBALS["i18n"] = i18n_load($config["language"] ?? "fr");

$to = $config["email_to"] ?? "";
$from = $config["email_from"] ?? "";
if ($to === "" || $from === "") {
    echo json_encode(["ok" => false, "error" => "missing" ]);
    exit;
}

$subject = ($config["mail_subject_prefix"] ?? "") . "Test notification";
$message = "This is a test notification from Domain Manager.\n";
$message .= "Time: " . (new DateTime())->format("Y-m-d H:i:s") . "\n";

$ok = send_mail($config, $to, $from, $subject, $message);
$error = $ok ? "" : mail_last_error();

echo json_encode(["ok" => $ok, "to" => $to, "error" => $error]);
