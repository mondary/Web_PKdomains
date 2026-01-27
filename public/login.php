<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/../app/lib/db.php";
date_default_timezone_set($config["timezone"]);

session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {
        $db = get_db($config);
        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE username = :u");
        $stmt->execute([":u" => $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $username;
            header("Location: index.php");
            exit;
        }
        $error = "Invalid credentials.";
    }
}
?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($config["site_name"]); ?> - Login</title>
    <link rel="stylesheet" href="assets/style.css">
  </head>
  <body>
    <div class="container">
      <?php
        $db_ok = is_writable(dirname($config["db_path"])) || is_file($config["db_path"]);
        if (!$db_ok) {
          echo '<div class="auth-error">SQLite: dossier secrets/data/ non inscriptible. Donne les droits d\'écriture.</div>';
        }
      ?>
      <div class="auth-card">
        <div class="auth-title">Sign in</div>
        <div class="auth-subtitle">Use your account to access the dashboard.</div>
        <?php if ($error): ?>
          <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
          <label class="auth-label" for="username">Username</label>
          <input class="auth-input" id="username" name="username" type="text" autocomplete="username" required>
          <label class="auth-label" for="password">Password</label>
          <input class="auth-input" id="password" name="password" type="password" autocomplete="current-password" required>
          <button class="btn primary auth-button" type="submit">Login</button>
        </form>
      </div>
    </div>
  </body>
</html>
