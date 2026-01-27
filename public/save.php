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

$db = get_db($config);
$original = trim($_POST["original_domain"] ?? "");
$domain = trim($_POST["domain"] ?? "");
$project = trim($_POST["project"] ?? "");
$registrar = trim($_POST["registrar"] ?? "");
$expires = trim($_POST["expires"] ?? "");
$status = trim($_POST["status"] ?? "Active");
$email = trim($_POST["email"] ?? "");

if ($domain === "") {
    header("Location: index.php");
    exit;
}

try {
    if ($original !== "") {
        $stmt = $db->prepare("UPDATE domains SET domain = :d, project = :p, registrar = :r, expires = :e, status = :s, email = :m WHERE domain = :od");
        $stmt->execute([
            ":d" => $domain,
            ":p" => $project,
            ":r" => $registrar,
            ":e" => $expires,
            ":s" => $status,
            ":m" => $email,
            ":od" => $original,
        ]);
    } else {
        $stmt = $db->prepare("INSERT INTO domains (domain, project, registrar, expires, status, email) VALUES (:d, :p, :r, :e, :s, :m)");
        $stmt->execute([
            ":d" => $domain,
            ":p" => $project,
            ":r" => $registrar,
            ":e" => $expires,
            ":s" => $status,
            ":m" => $email,
        ]);
    }
} catch (PDOException $e) {
    // Silent fail: return to list
}

header("Location: index.php");
exit;
