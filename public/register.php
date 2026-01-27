<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/../app/lib/db.php";
require_once __DIR__ . "/../app/lib/i18n.php";
require_once __DIR__ . "/../app/lib/users.php";
date_default_timezone_set($config["timezone"]);

$db = get_db($config);
$config = apply_settings($db, $config);
$GLOBALS["i18n"] = i18n_load($config["language"] ?? "fr");

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["password_confirm"] ?? "";
    if ($username === "" || $password === "") {
        $error = t("login_error_empty");
    } elseif ($password !== $confirm) {
        $error = t("password_mismatch");
    } else {
        try {
            create_user($db, $username, $password);
            header("Location: login.php");
            exit;
        } catch (Exception $e) {
            $error = t("register_failed");
        }
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($config["language"] ?? "fr"); ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($config["site_name"]); ?> - <?php echo t("register_title"); ?></title>
    <link rel="stylesheet" href="assets/style.css">
  </head>
  <body>
    <div class="container">
      <div class="auth-card">
        <div class="auth-title"><?php echo t("register_title"); ?></div>
        <div class="auth-subtitle"><?php echo t("register_subtitle"); ?></div>
        <?php if ($error): ?>
          <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
          <label class="auth-label" for="username"><?php echo t("label_username"); ?></label>
          <input class="auth-input" id="username" name="username" type="text" autocomplete="username" required>
          <label class="auth-label" for="password"><?php echo t("label_password"); ?></label>
          <input class="auth-input" id="password" name="password" type="password" autocomplete="new-password" required>
          <label class="auth-label" for="password_confirm"><?php echo t("label_password_confirm"); ?></label>
          <input class="auth-input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
          <button class="btn primary auth-button" type="submit"><?php echo t("register_button"); ?></button>
        </form>
        <div class="auth-links">
          <a class="link" href="login.php"><?php echo t("login_button"); ?></a>
        </div>
      </div>
    </div>
  </body>
</html>
