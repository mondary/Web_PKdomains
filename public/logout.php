<?php
require_once __DIR__ . "/app/lib/session.php";
secure_session_start();
session_unset();
session_destroy();

$script = $_SERVER["SCRIPT_NAME"] ?? "";
$dir = rtrim(str_replace("\\", "/", dirname($script)), "/");
if (substr($dir, -7) === "/public") {
    $dir = substr($dir, 0, -7);
}
$target = ($dir === "" ? "" : $dir) . "/index.php";
header("Location: " . $target);
exit;
