<?php
require_once __DIR__ . "/db.php";

function require_login(array $config): void {
    session_start();
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }
}
