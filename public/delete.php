<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/../app/lib/auth.php";
require_once __DIR__ . "/../app/lib/db.php";
date_default_timezone_set($config["timezone"]);
require_login($config);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$domain = trim($_POST["domain"] ?? "");
if ($domain !== "") {
    $db = get_db($config);
    $stmt = $db->prepare("DELETE FROM domains WHERE domain = :d");
    $stmt->execute([":d" => $domain]);
}

header("Location: index.php");
exit;
