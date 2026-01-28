<?php
require_once __DIR__ . "/url.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/session.php";

function require_login(array $config): void {
    secure_session_start();
    if (!isset($_SESSION["user_id"])) {
        header("Location: " . url_for($config, "public/login.php"));
        exit;
    }
}
