<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/../app/lib/auth.php";
require_once __DIR__ . "/../app/lib/db.php";
require_once __DIR__ . "/../app/lib/i18n.php";
require_once __DIR__ . "/../app/lib/users.php";
date_default_timezone_set($config["timezone"]);
require_login($config);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$db = get_db($config);
$config = apply_settings($db, $config);
$GLOBALS["i18n"] = i18n_load($config["language"] ?? "fr");

$current = $_POST["current_password"] ?? "";
$new = $_POST["new_password"] ?? "";
$confirm = $_POST["password_confirm"] ?? "";
$new_username = trim($_POST["new_username"] ?? "");

if ($current === "" || $new === "" || $confirm === "" || $new !== $confirm) {
    header("Location: index.php");
    exit;
}

$username = $_SESSION["username"] ?? "";
if ($username === "") {
    header("Location: index.php");
    exit;
}

$user = find_user($db, $username);
if (!$user || !password_verify($current, $user["password_hash"])) {
    header("Location: index.php");
    exit;
}

if ($new_username !== "" && $new_username !== $user["username"]) {
    $existing = find_user($db, $new_username);
    if ($existing) {
        header("Location: index.php");
        exit;
    }
    update_username($db, (int)$user["id"], $new_username);
    $_SESSION["username"] = $new_username;
}
update_user_password($db, (int)$user["id"], $new);
header("Location: index.php");
exit;
