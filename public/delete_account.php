<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/app/lib/auth.php";
require_once __DIR__ . "/app/lib/db.php";
require_once __DIR__ . "/app/lib/i18n.php";
require_once __DIR__ . "/app/lib/users.php";
date_default_timezone_set($config["timezone"]);
require_login($config);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$db = get_db($config);
$uid = (int)($_SESSION["user_id"] ?? 0);
$config = apply_settings($db, $config, $uid);
$GLOBALS["i18n"] = i18n_load($config["language"] ?? "fr");

$current = $_POST["current_password"] ?? "";
if ($current === "") {
    header("Location: index.php");
    exit;
}

$username = $_SESSION["username"] ?? "";
$user = $username ? find_user($db, $username) : null;
if (!$user || !password_verify($current, $user["password_hash"])) {
    header("Location: index.php");
    exit;
}

// Delete user data
$stmt = $db->prepare("DELETE FROM domains WHERE user_id = :uid");
$stmt->execute([":uid" => $uid]);
$stmt = $db->prepare("DELETE FROM settings WHERE user_id = :uid");
$stmt->execute([":uid" => $uid]);
$stmt = $db->prepare("DELETE FROM users WHERE id = :uid");
$stmt->execute([":uid" => $uid]);

session_unset();
session_destroy();
header("Location: login.php");
exit;
