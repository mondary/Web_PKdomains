<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/../app/lib/auth.php";
require_once __DIR__ . "/../app/lib/db.php";
date_default_timezone_set($config["timezone"]);
require_login($config);

$db = get_db($config);
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $domain = trim($_POST["domain"] ?? "");
    $project = trim($_POST["project"] ?? "");
    $registrar = trim($_POST["registrar"] ?? "");
    $expires = trim($_POST["expires"] ?? "");
    $status = trim($_POST["status"] ?? "Active");
    $email = trim($_POST["email"] ?? "");

    if ($domain === "") {
        $error = "Domain is required.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO domains (domain, project, registrar, expires, status, email) VALUES (:d, :p, :r, :e, :s, :m)");
            $stmt->execute([
                ":d" => $domain,
                ":p" => $project,
                ":r" => $registrar,
                ":e" => $expires,
                ":s" => $status,
                ":m" => $email,
            ]);
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $error = "Could not add domain (maybe already exists).";
        }
    }
}
?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($config["site_name"]); ?> - Add domain</title>
    <link rel="stylesheet" href="assets/style.css">
  </head>
  <body>
    <div class="container">
      <div class="auth-card">
        <div class="auth-title">Add domain</div>
        <div class="auth-subtitle">Create a new domain record.</div>
        <?php if ($error): ?>
          <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
          <label class="auth-label" for="domain">Domain *</label>
          <input class="auth-input" id="domain" name="domain" type="text" required>
          <button class="btn auth-button" type="button" data-autofill>Auto-fill registrar &amp; expiration</button>
          <div class="auth-hint" data-autofill-status></div>
          <label class="auth-label" for="project">Project</label>
          <input class="auth-input" id="project" name="project" type="text">
          <label class="auth-label" for="registrar">Registrar</label>
          <input class="auth-input" id="registrar" name="registrar" type="text" data-field-registrar>
          <label class="auth-label" for="expires">Expiration (YYYY-MM-DD)</label>
          <input class="auth-input" id="expires" name="expires" type="text" placeholder="2026-12-31" data-field-expires>
          <label class="auth-label" for="status">Status</label>
          <input class="auth-input" id="status" name="status" type="text" value="Active">
          <label class="auth-label" for="email">Alert email (optional)</label>
          <input class="auth-input" id="email" name="email" type="email">
          <button class="btn primary auth-button" type="submit">Save</button>
        </form>
      </div>
    </div>
  </body>
</html>
