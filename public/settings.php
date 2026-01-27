<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/app/lib/auth.php";
require_once __DIR__ . "/app/lib/db.php";
date_default_timezone_set($config["timezone"]);
require_login($config);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$db = get_db($config);
$uid = (int)($_SESSION["user_id"] ?? 0);

$site_name = trim($_POST["site_name"] ?? $config["site_name"]);
$email_to = trim($_POST["email_to"] ?? $config["email_to"]);
$email_from = trim($_POST["email_from"] ?? $config["email_from"]);
$mail_subject_prefix = trim($_POST["mail_subject_prefix"] ?? $config["mail_subject_prefix"]);
$days_raw = trim($_POST["alert_days"] ?? "");
$language = $_POST["language"] ?? ($config["language"] ?? "fr");
if (!in_array($language, ["fr", "en"], true)) {
    $language = "fr";
}
$columns_visible = $_POST["columns_visible"] ?? ["domain", "registrar", "expiration", "days", "status", "email", "project"];
if (!is_array($columns_visible)) {
    $columns_visible = ["domain", "registrar", "expiration", "days", "status", "email", "project"];
}

$days = [];
if ($days_raw !== "") {
    foreach (preg_split("/\\s*,\\s*/", $days_raw) as $d) {
        if ($d === "") continue;
        $days[] = (int)$d;
    }
}
if (empty($days)) {
    $days = $config["alert_days"];
}

$updates = [
    "site_name" => $site_name,
    "email_to" => $email_to,
    "email_from" => $email_from,
    "mail_subject_prefix" => $mail_subject_prefix,
    "alert_days" => json_encode(array_values($days)),
    "language" => $language,
    "columns_visible" => json_encode(array_values($columns_visible)),
];

$stmt = $db->prepare("INSERT OR REPLACE INTO settings (user_id, key, value) VALUES (:uid, :k, :v)");
foreach ($updates as $k => $v) {
    $stmt->execute([":uid" => $uid, ":k" => $k, ":v" => $v]);
}

header("Location: index.php");
exit;
