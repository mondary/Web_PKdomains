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
            $uid = (int)($_SESSION["user_id"] ?? 0);
            $stmt = $db->prepare("INSERT INTO domains (user_id, domain, project, registrar, expires, status, email) VALUES (:uid, :d, :p, :r, :e, :s, :m)");
            $stmt->execute([
                ":uid" => $uid,
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
            $error = t("error_add_failed");
        }
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($config["language"] ?? "fr"); ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($config["site_name"]); ?> - <?php echo t("drawer_add_title"); ?></title>
    <link rel="stylesheet" href="assets/style.css">
  </head>
  <body>
    <div class="container">
      <div class="auth-card">
        <div class="auth-title"><?php echo t("drawer_add_title"); ?></div>
        <div class="auth-subtitle"><?php echo t("header_hint"); ?></div>
        <?php if ($error): ?>
          <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
          <label class="auth-label" for="domain"><?php echo t("label_domain"); ?></label>
          <input class="auth-input" id="domain" name="domain" type="text" required>
          <button class="btn auth-button" type="button" data-autofill><?php echo t("autofill"); ?></button>
          <div class="auth-hint" data-autofill-status></div>
          <label class="auth-label" for="project"><?php echo t("label_project"); ?></label>
          <input class="auth-input" id="project" name="project" type="text">
          <label class="auth-label" for="registrar"><?php echo t("label_registrar"); ?></label>
          <input class="auth-input" id="registrar" name="registrar" type="text" data-field-registrar>
          <label class="auth-label" for="expires"><?php echo t("label_expiration"); ?></label>
          <div class="date-wrap">
            <input class="auth-input date-input" id="expires" name="expires" type="text" inputmode="numeric" maxlength="10" placeholder="YYYY-MM-DD" data-field-expires data-date-mask>
            <button class="date-btn" type="button" data-date-btn aria-label="<?php echo t("open_datepicker"); ?>">📅</button>
            <input class="date-native" type="date" tabindex="-1" aria-hidden="true">
          </div>
          <label class="auth-label" for="status"><?php echo t("label_status"); ?></label>
          <input class="auth-input" id="status" name="status" type="text" value="Active">
          <label class="auth-label" for="email"><?php echo t("label_email"); ?></label>
          <input class="auth-input" id="email" name="email" type="email">
          <button class="btn primary auth-button" type="submit"><?php echo t("save"); ?></button>
        </form>
      </div>
    </div>
    <script>
      window.I18N = {
        autofill_enter_domain: "<?php echo htmlspecialchars(t("autofill_enter_domain")); ?>",
        autofill_lookup: "<?php echo htmlspecialchars(t("autofill_lookup")); ?>",
        autofill_done: "<?php echo htmlspecialchars(t("autofill_done")); ?>",
        autofill_failed: "<?php echo htmlspecialchars(t("autofill_failed")); ?>"
      };
    </script>
  </body>
</html>
