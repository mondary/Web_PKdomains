<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/../app/lib/auth.php";
require_once __DIR__ . "/../app/lib/db.php";
require_once __DIR__ . "/../app/lib/i18n.php";
date_default_timezone_set($config["timezone"]);
require_login($config);

$db = get_db($config);
$config = apply_settings($db, $config);
$GLOBALS["i18n"] = i18n_load($config["language"] ?? "fr");
$error = "";

$domain_param = $_GET["domain"] ?? "";
$stmt = $db->prepare("SELECT * FROM domains WHERE domain = :d");
$stmt->execute([":d" => $domain_param]);
$row = $stmt->fetch();
if (!$row) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $domain = trim($_POST["domain"] ?? "");
    $project = trim($_POST["project"] ?? "");
    $registrar = trim($_POST["registrar"] ?? "");
    $expires = trim($_POST["expires"] ?? "");
    $status = trim($_POST["status"] ?? "Active");
    $email = trim($_POST["email"] ?? "");

    if ($domain === "") {
        $error = t("error_domain_required");
    } else {
        try {
            $stmt = $db->prepare("UPDATE domains SET domain = :d, project = :p, registrar = :r, expires = :e, status = :s, email = :m WHERE id = :id");
            $stmt->execute([
                ":d" => $domain,
                ":p" => $project,
                ":r" => $registrar,
                ":e" => $expires,
                ":s" => $status,
                ":m" => $email,
                ":id" => $row["id"],
            ]);
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $error = t("error_update_failed");
        }
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($config["language"] ?? "fr"); ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($config["site_name"]); ?> - <?php echo t("drawer_edit_title"); ?></title>
    <link rel="stylesheet" href="assets/style.css">
  </head>
  <body>
    <div class="container">
      <div class="auth-card">
        <div class="auth-title"><?php echo t("drawer_edit_title"); ?></div>
        <div class="auth-subtitle"><?php echo t("header_hint"); ?></div>
        <?php if ($error): ?>
          <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
          <label class="auth-label" for="domain"><?php echo t("label_domain"); ?></label>
          <input class="auth-input" id="domain" name="domain" type="text" required value="<?php echo htmlspecialchars($row["domain"]); ?>">
          <label class="auth-label" for="project"><?php echo t("label_project"); ?></label>
          <input class="auth-input" id="project" name="project" type="text" value="<?php echo htmlspecialchars($row["project"] ?? ""); ?>">
          <label class="auth-label" for="registrar"><?php echo t("label_registrar"); ?></label>
          <input class="auth-input" id="registrar" name="registrar" type="text" value="<?php echo htmlspecialchars($row["registrar"] ?? ""); ?>">
          <label class="auth-label" for="expires"><?php echo t("label_expiration"); ?></label>
          <input class="auth-input" id="expires" name="expires" type="text" value="<?php echo htmlspecialchars($row["expires"] ?? ""); ?>">
          <label class="auth-label" for="status"><?php echo t("label_status"); ?></label>
          <input class="auth-input" id="status" name="status" type="text" value="<?php echo htmlspecialchars($row["status"] ?? "Active"); ?>">
          <label class="auth-label" for="email"><?php echo t("label_email"); ?></label>
          <input class="auth-input" id="email" name="email" type="email" value="<?php echo htmlspecialchars($row["email"] ?? ""); ?>">
          <button class="btn primary auth-button" type="submit"><?php echo t("save"); ?></button>
        </form>
      </div>
    </div>
  </body>
</html>
