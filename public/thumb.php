<?php
$config = require __DIR__ . "/config.php";
require_once __DIR__ . "/app/lib/auth.php";
require_once __DIR__ . "/app/lib/db.php";
require_once __DIR__ . "/app/lib/thumbs.php";
date_default_timezone_set($config["timezone"]);
require_login($config);

header("Content-Type: application/json; charset=utf-8");

$domain = $_GET["domain"] ?? "";
if ($domain === "") {
    echo json_encode(["ok" => false, "error" => "missing domain"]);
    exit;
}

$db = get_db($config);
$uid = (int)($_SESSION["user_id"] ?? 0);
$stmt = $db->prepare("SELECT id FROM domains WHERE domain = :d AND user_id = :uid");
$stmt->execute([":d" => $domain, ":uid" => $uid]);
if (!$stmt->fetchColumn()) {
    echo json_encode(["ok" => false, "error" => "forbidden"]);
    exit;
}

$cached = get_cached_thumbnail($domain);
if ($cached) {
    echo json_encode(["ok" => true, "url" => $cached, "cached" => true]);
    exit;
}

$url = cache_site_thumbnail($domain, false);
if ($url) {
    echo json_encode(["ok" => true, "url" => $url, "cached" => false]);
    exit;
}

echo json_encode(["ok" => false]);
