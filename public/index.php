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
$domains = $db->query("SELECT domain, project, registrar, expires, status, email FROM domains ORDER BY domain ASC")->fetchAll();

function days_until($date) {
    $today = new DateTime("today");
    $exp = DateTime::createFromFormat("Y-m-d", $date);
    if (!$exp) {
        return null;
    }
    $diff = $today->diff($exp);
    return (int)$diff->format("%r%a");
}

function status_class($days) {
    if ($days === null) return "warn";
    if ($days <= 7) return "danger";
    if ($days <= 30) return "warn";
    return "ok";
}

function registrar_logo_url($registrar) {
    $r = strtolower(trim((string)$registrar));
    if ($r === "") return null;
    $map = [
        "ovh" => "ovh.com",
        "ovh sas" => "ovh.com",
        "spaceship" => "spaceship.com",
        "spaceship, inc." => "spaceship.com",
        "key-systems" => "key-systems.net",
        "key-systems gmbh" => "key-systems.net",
        "namecheap" => "namecheap.com",
        "godaddy" => "godaddy.com",
        "gandi" => "gandi.net",
        "cloudflare" => "cloudflare.com",
    ];
    foreach ($map as $key => $domain) {
        if (strpos($r, $key) !== false) {
            return "https://logo.clearbit.com/" . $domain;
        }
    }
    return null;
}

$total = count($domains);
$expiring = 0;
foreach ($domains as $d) {
    $days = days_until($d["expires"] ?? "");
    if ($days !== null && $days <= 30) $expiring++;
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($config["language"] ?? "fr"); ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($config["site_name"]); ?></title>
    <link rel="stylesheet" href="assets/style.css">
  </head>
  <body>
    <div class="topbar">
      <div class="topbar-inner">
        <div class="brand"><?php echo htmlspecialchars($config["site_name"]); ?></div>
        <div class="nav"></div>
        <div class="spacer"></div>
        <button class="topbar-action" type="button" data-settings-open><?php echo t("options"); ?></button>
        <a class="topbar-action" href="logout.php"><?php echo t("logout"); ?></a>
        <div class="badge"><?php echo t("expiring_badge", ["count" => $expiring]); ?></div>
      </div>
    </div>

    <div class="container">
      <div class="header">
        <h1><?php echo t("domains"); ?></h1>
        <div class="hint"><?php echo t("header_hint"); ?></div>
      </div>

      <div class="controls">
        <div class="search">
          <span class="muted">🔎</span>
          <input data-search type="text" placeholder="<?php echo t("search_placeholder"); ?>">
        </div>
        <select class="select">
          <option><?php echo t("label_alert_days"); ?>: <?php echo htmlspecialchars(implode(", ", $config["alert_days"])); ?></option>
        </select>
        <button class="btn primary" type="button" data-drawer-open="add"><?php echo t("add_domain"); ?></button>
      </div>

      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th><?php echo t("table_domain"); ?></th>
              <th><?php echo t("table_project"); ?></th>
              <th><?php echo t("table_registrar"); ?></th>
              <th><?php echo t("table_expiration"); ?></th>
              <th><?php echo t("table_days_left"); ?></th>
              <th><?php echo t("table_status"); ?></th>
              <th><?php echo t("table_email"); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($domains as $d): ?>
              <?php $days = days_until($d["expires"] ?? ""); ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($d["domain"] ?? ""); ?></strong></td>
                <td class="muted"><?php echo htmlspecialchars($d["project"] ?? ""); ?></td>
                <td>
                  <div class="registrar">
                    <?php $logo = registrar_logo_url($d["registrar"] ?? ""); ?>
                    <?php if ($logo): ?>
                      <img class="registrar-logo" src="<?php echo htmlspecialchars($logo); ?>" alt="">
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($d["registrar"] ?? ""); ?></span>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($d["expires"] ?? ""); ?></td>
                <td><?php echo $days === null ? "—" : $days; ?></td>
                <td><span class="pill <?php echo status_class($days); ?>">
                  <?php
                    if ($days === null) echo t("status_unknown");
                    elseif ($days <= 7) echo t("status_critical");
                    elseif ($days <= 30) echo t("status_soon");
                    else echo t("status_ok");
                  ?>
                </span></td>
                <td class="muted"><?php echo htmlspecialchars(($d["email"] ?? "") ?: $config["email_to"]); ?></td>
                <td class="actions">
                  <button class="link" type="button"
                    data-drawer-open="edit"
                    data-domain="<?php echo htmlspecialchars($d["domain"] ?? ""); ?>"
                    data-project="<?php echo htmlspecialchars($d["project"] ?? ""); ?>"
                    data-registrar="<?php echo htmlspecialchars($d["registrar"] ?? ""); ?>"
                    data-expires="<?php echo htmlspecialchars($d["expires"] ?? ""); ?>"
                    data-status="<?php echo htmlspecialchars($d["status"] ?? ""); ?>"
                    data-email="<?php echo htmlspecialchars($d["email"] ?? ""); ?>"
                  title="<?php echo t("edit"); ?>" aria-label="<?php echo t("edit"); ?>">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" aria-hidden="true" focusable="false"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M416.9 85.2L372 130.1L509.9 268L554.8 223.1C568.4 209.6 576 191.2 576 172C576 152.8 568.4 134.4 554.8 120.9L519.1 85.2C505.6 71.6 487.2 64 468 64C448.8 64 430.4 71.6 416.9 85.2zM338.1 164L122.9 379.1C112.2 389.8 104.4 403.2 100.3 417.8L64.9 545.6C62.6 553.9 64.9 562.9 71.1 569C77.3 575.1 86.2 577.5 94.5 575.2L222.3 539.7C236.9 535.6 250.2 527.9 261 517.1L476 301.9L338.1 164z"/></svg>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="footer">
        Alert thresholds: <?php echo implode(", ", $config["alert_days"]); ?> days.
      </div>
    </div>
    <div class="drawer-backdrop" data-drawer-backdrop></div>
    <div class="drawer" data-drawer>
      <div class="drawer-header">
        <div class="drawer-title" data-drawer-title><?php echo t("drawer_add_title"); ?></div>
        <button class="topbar-action" type="button" data-drawer-close><?php echo t("close"); ?></button>
      </div>
      <form method="post" action="save.php" class="drawer-body">
        <input type="hidden" name="original_domain" value="">
        <label class="auth-label" for="d-domain"><?php echo t("label_domain"); ?></label>
        <input class="auth-input" id="d-domain" name="domain" type="text" required>
        <button class="btn auth-button" type="button" data-autofill><?php echo t("autofill"); ?></button>
        <div class="auth-hint" data-autofill-status></div>
        <label class="auth-label" for="d-project"><?php echo t("label_project"); ?></label>
        <input class="auth-input" id="d-project" name="project" type="text">
        <label class="auth-label" for="d-registrar"><?php echo t("label_registrar"); ?></label>
        <input class="auth-input" id="d-registrar" name="registrar" type="text" data-field-registrar>
        <label class="auth-label" for="d-expires"><?php echo t("label_expiration"); ?></label>
        <input class="auth-input" id="d-expires" name="expires" type="text" placeholder="2026-12-31" data-field-expires>
        <label class="auth-label" for="d-status"><?php echo t("label_status"); ?></label>
        <input class="auth-input" id="d-status" name="status" type="text" value="Active">
        <label class="auth-label" for="d-email"><?php echo t("label_email"); ?></label>
        <input class="auth-input" id="d-email" name="email" type="email">
        <button class="btn primary auth-button" type="submit"><?php echo t("save"); ?></button>
      </form>
      <form method="post" action="delete.php" class="drawer-footer">
        <input type="hidden" name="domain" value="">
        <button class="link danger" type="submit" onclick="return confirm('<?php echo t("delete_confirm"); ?>');"><?php echo t("delete_domain"); ?></button>
      </form>
    </div>
    <div class="drawer" data-settings-drawer>
      <div class="drawer-header">
        <div class="drawer-title"><?php echo t("options_title"); ?></div>
        <button class="topbar-action" type="button" data-settings-close><?php echo t("close"); ?></button>
      </div>
      <form method="post" action="settings.php" class="drawer-body">
        <label class="auth-label" for="s-site"><?php echo t("label_site_name"); ?></label>
        <input class="auth-input" id="s-site" name="site_name" type="text" value="<?php echo htmlspecialchars($config["site_name"]); ?>">
        <label class="auth-label" for="s-email-to"><?php echo t("label_email_to"); ?></label>
        <input class="auth-input" id="s-email-to" name="email_to" type="email" value="<?php echo htmlspecialchars($config["email_to"]); ?>">
        <label class="auth-label" for="s-email-from"><?php echo t("label_email_from"); ?></label>
        <input class="auth-input" id="s-email-from" name="email_from" type="email" value="<?php echo htmlspecialchars($config["email_from"]); ?>">
        <label class="auth-label" for="s-prefix"><?php echo t("label_prefix"); ?></label>
        <input class="auth-input" id="s-prefix" name="mail_subject_prefix" type="text" value="<?php echo htmlspecialchars($config["mail_subject_prefix"]); ?>">
        <label class="auth-label" for="s-lang"><?php echo t("label_language"); ?></label>
        <select class="select" id="s-lang" name="language">
          <option value="fr" <?php echo ($config["language"] ?? "fr") === "fr" ? "selected" : ""; ?>>FR</option>
          <option value="en" <?php echo ($config["language"] ?? "fr") === "en" ? "selected" : ""; ?>>EN</option>
        </select>
        <label class="auth-label" for="s-days"><?php echo t("label_alert_days"); ?></label>
        <input class="auth-input" id="s-days" name="alert_days" type="text" value="<?php echo htmlspecialchars(implode(", ", $config["alert_days"])); ?>">
        <button class="btn primary auth-button" type="submit"><?php echo t("save_options"); ?></button>
      </form>
    </div>
    <script>
      window.I18N = {
        autofill_enter_domain: "<?php echo htmlspecialchars(t("autofill_enter_domain")); ?>",
        autofill_lookup: "<?php echo htmlspecialchars(t("autofill_lookup")); ?>",
        autofill_done: "<?php echo htmlspecialchars(t("autofill_done")); ?>",
        autofill_failed: "<?php echo htmlspecialchars(t("autofill_failed")); ?>"
      };
    </script>
    <script src="assets/app.js"></script>
  </body>
</html>
