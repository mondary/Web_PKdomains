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

$domain = trim($_POST["domain"] ?? "");
if ($domain !== "") {
    $db = get_db($config);
    $uid = (int)($_SESSION["user_id"] ?? 0);
    $stmt = $db->prepare("DELETE FROM domains WHERE domain = :d AND user_id = :uid");
    $stmt->execute([":d" => $domain, ":uid" => $uid]);
}

header("Location: index.php");
exit;
