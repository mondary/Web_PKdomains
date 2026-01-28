<?php
$config = require __DIR__ . "/config.php";
require_once __DIR__ . "/app/lib/url.php";
require_once __DIR__ . "/app/lib/db.php";
require_once __DIR__ . "/app/lib/i18n.php";
require_once __DIR__ . "/app/lib/session.php";
date_default_timezone_set($config["timezone"]);

secure_session_start();
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . url_for($config, "index.php"));
    exit;
}
if (isset($_SESSION["user_id"])) {
    header("Location: " . url_for($config, "index.php"));
    exit;
}

$db = get_db($config);
$config = apply_settings($db, $config, null);
$GLOBALS["i18n"] = i18n_load($config["language"] ?? "fr");

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    if ($username === "" || $password === "") {
        $error = t("login_error_empty");
    } else {
        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE username = :u");
        $stmt->execute([":u" => $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $username;
            header("Location: " . url_for($config, "index.php"));
            exit;
        }
        $error = t("login_error_invalid");
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($config["language"] ?? "fr"); ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($config["site_name"]); ?> - <?php echo t("login_title"); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(url_for($config, "public/assets/style.css")); ?>?v=<?php echo urlencode($config["version"] ?? ""); ?>">
  </head>
  <body>
    <div class="container">
      <?php
        $db_ok = is_writable(dirname($config["db_path"])) || is_file($config["db_path"]);
        if (!$db_ok) {
          echo '<div class="auth-error">' . htmlspecialchars(t("login_sqlite_perm")) . '</div>';
        }
      ?>
      <div class="auth-card">
        <div class="auth-title"><?php echo t("login_title"); ?></div>
        <div class="auth-subtitle"><?php echo t("login_subtitle"); ?></div>
        <?php if ($error): ?>
          <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
          <label class="auth-label" for="username"><?php echo t("label_username"); ?></label>
          <input class="auth-input" id="username" name="username" type="text" autocomplete="username" required>
          <label class="auth-label" for="password"><?php echo t("label_password"); ?></label>
          <input class="auth-input" id="password" name="password" type="password" autocomplete="current-password" required>
          <button class="btn primary auth-button" type="submit"><?php echo t("login_button"); ?></button>
        </form>
        <div class="auth-links">
          <a class="link" href="<?php echo htmlspecialchars(url_for($config, "public/register.php")); ?>"><?php echo t("register_link"); ?></a>
        </div>
      </div>
    </div>
  </body>
</html>
