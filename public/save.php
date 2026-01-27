<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/../app/lib/auth.php";
require_once __DIR__ . "/../app/lib/db.php";
require_once __DIR__ . "/../app/lib/rdap.php";
require_once __DIR__ . "/../app/lib/logos.php";
require_once __DIR__ . "/../app/lib/thumbs.php";
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

if ($registrar === "" || $expires === "") {
    $rdap = rdap_lookup($config, $domain);
    if ($rdap["ok"] ?? false) {
        if ($registrar === "" && !empty($rdap["registrar"])) {
            $registrar = $rdap["registrar"];
        }
        if ($expires === "" && !empty($rdap["expiration"])) {
            $expires = $rdap["expiration"];
        }
    }
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
    if ($registrar !== "") {
        cache_registrar_logo($registrar);
    }
    if ($domain !== "") {
        cache_site_thumbnail($domain);
    }
} catch (PDOException $e) {
    // Silent fail: return to list
}

header("Location: index.php");
exit;
