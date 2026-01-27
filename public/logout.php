<?php
session_start();
session_unset();
session_destroy();
header("Location: " . url_for($config, "index.php"));
exit;
