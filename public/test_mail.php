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

function days_until(string $date): ?int {
    $today = new DateTime("today");
    $exp = DateTime::createFromFormat("Y-m-d", $date);
    if (!$exp) {
        return null;
    }
    $diff = $today->diff($exp);
    return (int)$diff->format("%r%a");
}

$uid = (int)($_SESSION["user_id"] ?? 0);
$stmt = $db->prepare("SELECT domain, expires, registrar, project FROM domains WHERE user_id = :uid ORDER BY domain ASC");
$stmt->execute([":uid" => $uid]);
$domains = $stmt->fetchAll();
foreach ($domains as &$d) {
    $d["days"] = isset($d["expires"]) ? days_until($d["expires"]) : null;
}
unset($d);
usort($domains, function($a, $b) {
    $da = $a["days"] ?? 99999;
    $db = $b["days"] ?? 99999;
    if ($da === $db) return 0;
    return $da < $db ? -1 : 1;
});

$subject = ($config["mail_subject_prefix"] ?? "") . "Test notification";
$time = (new DateTime())->format("Y-m-d H:i:s");
$text = "Test notification\n\nTime: {$time}\nTo: {$to}\nFrom: {$from}\n\nDomains recap:\n";
foreach ($domains as $d) {
    $days = $d["days"] === null ? "—" : $d["days"];
    $text .= "- {$d["domain"]} | {$days} days | {$d["expires"]}\n";
}

$html = "<div style=\"font-family:Arial,Helvetica,sans-serif; color:#0f172a;\">\n";
$html .= "<h2 style=\"margin:0 0 10px;\">Test notification</h2>\n";
$html .= "<p style=\"margin:0 0 14px; color:#475569;\">This is a test message from Domain Manager.</p>\n";
$html .= "<table style=\"border-collapse:collapse; font-size:14px;\">\n";
$html .= "<tr><td style=\"padding:6px 10px; color:#64748b;\">Time</td><td style=\"padding:6px 10px;\">{$time}</td></tr>\n";
$html .= "<tr><td style=\"padding:6px 10px; color:#64748b;\">To</td><td style=\"padding:6px 10px;\">{$to}</td></tr>\n";
$html .= "<tr><td style=\"padding:6px 10px; color:#64748b;\">From</td><td style=\"padding:6px 10px;\">{$from}</td></tr>\n";
$html .= "</table>\n";
$html .= "<h3 style=\"margin:18px 0 8px;\">Domains recap</h3>\n";
$html .= "<table style=\"border-collapse:collapse; width:100%; font-size:14px;\">\n";
$html .= "<thead><tr>";
$html .= "<th style=\"text-align:left; padding:6px 8px; border-bottom:1px solid #e2e8f0; color:#64748b;\">Domain</th>";
$html .= "<th style=\"text-align:left; padding:6px 8px; border-bottom:1px solid #e2e8f0; color:#64748b;\">Days left</th>";
$html .= "<th style=\"text-align:left; padding:6px 8px; border-bottom:1px solid #e2e8f0; color:#64748b;\">Expires</th>";
$html .= "</tr></thead><tbody>";
foreach ($domains as $d) {
    $days = $d["days"] === null ? "—" : $d["days"];
    $exp = htmlspecialchars($d["expires"] ?? "");
    $dom = htmlspecialchars($d["domain"] ?? "");
    $html .= "<tr>";
    $html .= "<td style=\"padding:6px 8px; border-bottom:1px solid #f1f5f9;\">{$dom}</td>";
    $html .= "<td style=\"padding:6px 8px; border-bottom:1px solid #f1f5f9;\">{$days}</td>";
    $html .= "<td style=\"padding:6px 8px; border-bottom:1px solid #f1f5f9;\">{$exp}</td>";
    $html .= "</tr>";
}
$html .= "</tbody></table>\n";
$html .= "<p style=\"margin:16px 0 0; color:#94a3b8; font-size:12px;\">If you received this email, SMTP is configured correctly.</p>\n";
$html .= "</div>";

$ok = send_mail_html($config, $to, $from, $subject, $html, $text);
$error = $ok ? "" : mail_last_error();

echo json_encode(["ok" => $ok, "to" => $to, "error" => $error]);
