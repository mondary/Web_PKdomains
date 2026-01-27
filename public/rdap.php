<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/../app/lib/auth.php";
require_once __DIR__ . "/../app/lib/rdap.php";
date_default_timezone_set($config["timezone"]);
require_login($config);

header("Content-Type: application/json; charset=utf-8");

$domain = $_GET["domain"] ?? "";
$result = rdap_lookup($config, $domain);
echo json_encode($result);
