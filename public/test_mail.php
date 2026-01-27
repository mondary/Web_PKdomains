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
$time = (new DateTime())->format("Y-m-d H:i:s");
$text = "Test notification\n\nTime: {$time}\nTo: {$to}\nFrom: {$from}\n";
$html = "<div style=\"font-family:Arial,Helvetica,sans-serif; color:#0f172a;\">\n";
$html .= "<h2 style=\"margin:0 0 10px;\">Test notification</h2>\n";
$html .= "<p style=\"margin:0 0 14px; color:#475569;\">This is a test message from Domain Manager.</p>\n";
$html .= "<table style=\"border-collapse:collapse; font-size:14px;\">\n";
$html .= "<tr><td style=\"padding:6px 10px; color:#64748b;\">Time</td><td style=\"padding:6px 10px;\">{$time}</td></tr>\n";
$html .= "<tr><td style=\"padding:6px 10px; color:#64748b;\">To</td><td style=\"padding:6px 10px;\">{$to}</td></tr>\n";
$html .= "<tr><td style=\"padding:6px 10px; color:#64748b;\">From</td><td style=\"padding:6px 10px;\">{$from}</td></tr>\n";
$html .= "</table>\n";
$html .= "<p style=\"margin:16px 0 0; color:#94a3b8; font-size:12px;\">If you received this email, SMTP is configured correctly.</p>\n";
$html .= "</div>";

$ok = send_mail_html($config, $to, $from, $subject, $html, $text);
$error = $ok ? "" : mail_last_error();

echo json_encode(["ok" => $ok, "to" => $to, "error" => $error]);
