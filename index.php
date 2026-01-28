<?php
$config = require __DIR__ . "/public/config.php";
require_once __DIR__ . "/public/app/lib/url.php";
require_once __DIR__ . "/public/app/lib/i18n.php";
require_once __DIR__ . "/public/app/lib/session.php";
date_default_timezone_set($config["timezone"]);

secure_session_start();
if (!isset($_SESSION["user_id"])) {
    require __DIR__ . "/public/demo.php";
    exit;
}

require_once __DIR__ . "/public/app/lib/db.php";
require_once __DIR__ . "/public/app/lib/logos.php";
require_once __DIR__ . "/public/app/lib/thumbs.php";
require_once __DIR__ . "/public/app/lib/users.php";

$db = get_db($config);
$uid = (int)($_SESSION["user_id"] ?? 0);
$config = apply_settings($db, $config, $uid);
$GLOBALS["i18n"] = i18n_load($config["language"] ?? "fr");
$visible_cols = $config["columns_visible"] ?? ["domain", "registrar", "expiration", "days", "status", "email", "project"];
$is_visible = function ($key) use ($visible_cols) {
    return in_array($key, $visible_cols, true);
};
$stmt = $db->prepare("SELECT domain, project, registrar, expires, status, email FROM domains WHERE user_id = :uid ORDER BY domain ASC");
$stmt->execute([":uid" => $uid]);
$domains = $stmt->fetchAll();

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
    if ($days === null) return "unknown";
    if ($days <= 7) return "danger";
    if ($days <= 30) return "warn";
    return "ok";
}

function registrar_logo_url($registrar) {
    $domain = registrar_domain_for_logo((string)$registrar);
    if (!$domain) return null;
    return "https://logo.clearbit.com/" . $domain;
}

function registrar_site_url($registrar) {
    $r = strtolower(trim((string)$registrar));
    foreach (registrar_url_map() as $key => $url) {
        if (strpos($r, $key) !== false) {
            return $url;
        }
    }
    $domain = registrar_domain_for_logo((string)$registrar);
    if (!$domain) return null;
    return "https://www." . $domain . "/";
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars(url_for($config, "public/assets/style.css")); ?>?v=<?php echo urlencode($config["version"] ?? ""); ?>">
  </head>
  <body data-theme="<?php echo htmlspecialchars($config["theme"] ?? "light"); ?>">
    <div class="watermark" style="background-image:url('<?php echo htmlspecialchars(url_for($config, "icon.png")); ?>');"></div>
    <div class="topbar">
      <div class="topbar-inner">
        <div class="brand">
          <img class="brand-logo" src="<?php echo htmlspecialchars(url_for($config, "icon.png")); ?>" alt="">
          <span><?php echo htmlspecialchars($config["site_name"]); ?></span>
        </div>
        <div class="nav"></div>
        <button class="burger" type="button" aria-label="Menu" data-burger-open>☰</button>
        <form class="domain-garden-search" action="https://domain.garden/" method="get" target="_blank" rel="noopener noreferrer">
          <img src="https://domain.garden/favicon.ico" alt="" class="domain-garden-logo" referrerpolicy="no-referrer">
          <input class="domain-garden-input" type="search" name="q" placeholder="<?php echo t("search_domains"); ?>">
        </form>
        <div class="spacer"></div>
        <button class="topbar-action" type="button" data-settings-open><?php echo t("options"); ?></button>
        <a class="topbar-action" href="<?php echo htmlspecialchars(url_for($config, "public/logout.php")); ?>"><?php echo t("logout"); ?></a>
        <div class="topbar-menu" data-burger-menu>
          <a class="topbar-menu-item" href="https://domain.garden/" target="_blank" rel="noopener noreferrer"><?php echo t("search_domains"); ?></a>
          <button class="topbar-menu-item" type="button" data-settings-open><?php echo t("options"); ?></button>
          <a class="topbar-menu-item danger" href="<?php echo htmlspecialchars(url_for($config, "public/logout.php")); ?>"><?php echo t("logout"); ?></a>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="header">
        <div>
          <div class="hint"><?php echo t("header_hint"); ?></div>
        </div>
      </div>

      <div class="controls">
        <div class="search">
          <span class="muted">🔎</span>
          <input data-search type="text" placeholder="<?php echo t("search_placeholder"); ?>">
        </div>
        <select class="select" data-days-filter>
          <option value="all" selected><?php echo t("filter_all"); ?></option>
          <option value="30">&lt; 30j</option>
          <option value="60">&lt; 60j</option>
          <option value="90">&lt; 90j</option>
          <option value="180">&lt; 180j</option>
        </select>
        <button class="btn primary" type="button" data-drawer-open="add"><?php echo t("add_domain"); ?></button>
      </div>

      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th class="<?php echo $is_visible("domain") ? "" : "col-hidden"; ?>" data-col="domain"><button class="sort-btn" data-sort="domain"><?php echo t("table_domain"); ?> (<?php echo $total; ?>)</button></th>
              <th class="<?php echo $is_visible("registrar") ? "" : "col-hidden"; ?>" data-col="registrar"><button class="sort-btn" data-sort="registrar"><?php echo t("table_registrar"); ?></button></th>
              <th class="<?php echo $is_visible("expiration") ? "" : "col-hidden"; ?>" data-col="expiration"><button class="sort-btn" data-sort="expiration"><?php echo t("table_expiration"); ?></button></th>
              <th class="<?php echo $is_visible("days") ? "" : "col-hidden"; ?>" data-col="days"><button class="sort-btn" data-sort="days"><?php echo t("table_days_left"); ?></button></th>
              <th class="<?php echo $is_visible("status") ? "" : "col-hidden"; ?>" data-col="status"><button class="sort-btn" data-sort="status"><?php echo t("table_status"); ?></button></th>
              <th class="<?php echo $is_visible("email") ? "" : "col-hidden"; ?>" data-col="email"><button class="sort-btn" data-sort="email"><?php echo t("table_email"); ?></button></th>
              <th class="<?php echo $is_visible("project") ? "" : "col-hidden"; ?>" data-col="project"><button class="sort-btn" data-sort="project"><?php echo t("table_project"); ?></button></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($domains as $d): ?>
              <?php $days = days_until($d["expires"] ?? ""); ?>
              <tr tabindex="0"
                  data-domain="<?php echo htmlspecialchars($d["domain"] ?? ""); ?>"
                  data-project="<?php echo htmlspecialchars($d["project"] ?? ""); ?>"
                  data-registrar="<?php echo htmlspecialchars($d["registrar"] ?? ""); ?>"
                  data-expires="<?php echo htmlspecialchars($d["expires"] ?? ""); ?>"
                  data-status="<?php echo htmlspecialchars($d["status"] ?? ""); ?>"
                  data-email="<?php echo htmlspecialchars($d["email"] ?? ""); ?>"
              >
                <td class="domain-cell <?php echo $is_visible("domain") ? "" : "col-hidden"; ?>" data-col="domain" data-label="<?php echo t("table_domain"); ?>">
                  <?php $domain = htmlspecialchars($d["domain"] ?? ""); ?>
                  <?php $thumb = get_cached_thumbnail($d["domain"] ?? ""); ?>
                  <?php $favicon = cache_favicon($d["domain"] ?? ""); ?>
                  <span class="domain-media">
                    <?php if ($thumb): ?>
                    <img class="thumb" src="<?php echo htmlspecialchars($thumb); ?>" alt="" data-thumb-loaded="1">
                  <?php else: ?>
                    <span class="thumb placeholder" data-thumb-missing="1"></span>
                  <?php endif; ?>
                  </span>
                  <?php
                    $reg_logo = registrar_logo_url($d["registrar"] ?? "");
                    $reg_cached = cache_registrar_logo($d["registrar"] ?? "");
                  ?>
                  <span class="domain-title">
                    <?php if ($favicon): ?>
                      <img class="favicon" src="<?php echo htmlspecialchars($favicon); ?>" alt="">
                    <?php else: ?>
                      <span class="favicon placeholder"></span>
                    <?php endif; ?>
                    <a class="link" href="https://<?php echo $domain; ?>" target="_blank" rel="noopener noreferrer"><?php echo $domain; ?></a>
                  </span>
                  <span class="domain-registrar-line">
                    <?php if ($reg_logo || $reg_cached): ?>
                      <img class="registrar-logo" src="<?php echo htmlspecialchars($reg_cached ?: $reg_logo); ?>" alt="" referrerpolicy="no-referrer">
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($d["registrar"] ?? ""); ?></span>
                  </span>
                </td>
                <td class="<?php echo $is_visible("registrar") ? "" : "col-hidden"; ?>" data-col="registrar" data-label="<?php echo t("table_registrar"); ?>">
                  <div class="registrar">
                    <?php $logo = registrar_logo_url($d["registrar"] ?? ""); ?>
                    <?php $cached_logo = cache_registrar_logo($d["registrar"] ?? ""); ?>
                    <?php $reg_url = registrar_site_url($d["registrar"] ?? ""); ?>
                    <?php if ($logo): ?>
                      <?php $alt_logo = str_replace("https://logo.clearbit.com/", "https://icons.duckduckgo.com/ip3/", $logo) . ".ico"; ?>
                      <img class="registrar-logo" src="<?php echo htmlspecialchars($cached_logo ?: $logo); ?>" data-alt-src="<?php echo htmlspecialchars($alt_logo); ?>" alt="" referrerpolicy="no-referrer">
                    <?php endif; ?>
                    <?php if ($reg_url): ?>
                      <a class="link" href="<?php echo htmlspecialchars($reg_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($d["registrar"] ?? ""); ?></a>
                    <?php else: ?>
                      <span><?php echo htmlspecialchars($d["registrar"] ?? ""); ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="<?php echo $is_visible("expiration") ? "" : "col-hidden"; ?>" data-col="expiration" data-label="<?php echo t("table_expiration"); ?>"><?php echo htmlspecialchars($d["expires"] ?? ""); ?></td>
                <td class="<?php echo $is_visible("days") ? "" : "col-hidden"; ?>" data-col="days" data-label="<?php echo t("table_days_left"); ?>"><?php echo $days === null ? "—" : $days; ?></td>
                <td class="<?php echo $is_visible("status") ? "" : "col-hidden"; ?>" data-col="status" data-label="<?php echo t("table_status"); ?>"><span class="pill <?php echo status_class($days); ?>">
                  <?php
                    if ($days === null) echo t("status_unknown");
                    elseif ($days <= 7) echo t("status_critical");
                    elseif ($days <= 30) echo t("status_soon");
                    else echo t("status_ok");
                  ?>
                </span></td>
                <td class="muted <?php echo $is_visible("email") ? "" : "col-hidden"; ?>" data-col="email" data-label="<?php echo t("table_email"); ?>"><?php echo htmlspecialchars(($d["email"] ?? "") ?: $config["email_to"]); ?></td>
                <td class="muted <?php echo $is_visible("project") ? "" : "col-hidden"; ?>" data-col="project" data-label="<?php echo t("table_project"); ?>"><?php echo htmlspecialchars($d["project"] ?? ""); ?></td>
                <td class="actions" data-label="<?php echo t("edit"); ?>">
                  <button class="icon-button" type="button"
                    data-drawer-open="edit"
                    data-domain="<?php echo htmlspecialchars($d["domain"] ?? ""); ?>"
                    data-project="<?php echo htmlspecialchars($d["project"] ?? ""); ?>"
                    data-registrar="<?php echo htmlspecialchars($d["registrar"] ?? ""); ?>"
                    data-expires="<?php echo htmlspecialchars($d["expires"] ?? ""); ?>"
                    data-status="<?php echo htmlspecialchars($d["status"] ?? ""); ?>"
                    data-email="<?php echo htmlspecialchars($d["email"] ?? ""); ?>"
                  title="<?php echo t("edit"); ?>" aria-label="<?php echo t("edit"); ?>">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" aria-hidden="true" focusable="false"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M416.9 85.2L372 130.1L509.9 268L554.8 223.1C568.4 209.6 576 191.2 576 172C576 152.8 568.4 134.4 554.8 120.9L519.1 85.2C505.6 71.6 487.2 64 468 64C448.8 64 430.4 71.6 416.9 85.2zM338.1 164L122.9 379.1C112.2 389.8 104.4 403.2 100.3 417.8L64.9 545.6C62.6 553.9 64.9 562.9 71.1 569C77.3 575.1 86.2 577.5 94.5 575.2L222.3 539.7C236.9 535.6 250.2 527.9 261 517.1L476 301.9L338.1 164z"/></svg>
                    <span class="edit-label">Éditer</span>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      
    </div>
    <div class="drawer-backdrop" data-drawer-backdrop></div>
    <div class="drawer" data-drawer>
      <div class="drawer-header">
        <div class="drawer-title" data-drawer-title><?php echo t("drawer_add_title"); ?></div>
        <button class="topbar-action" type="button" data-drawer-close><?php echo t("close"); ?></button>
      </div>
      <form method="post" action="<?php echo htmlspecialchars(url_for($config, "public/save.php")); ?>" class="drawer-body">
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
        <div class="date-wrap">
          <input class="auth-input date-input" id="d-expires" name="expires" type="text" inputmode="numeric" maxlength="10" placeholder="YYYY-MM-DD" data-field-expires data-date-mask>
          <button class="date-btn" type="button" data-date-btn aria-label="<?php echo t("open_datepicker"); ?>">📅</button>
          <input class="date-native" type="date" tabindex="-1" aria-hidden="true">
        </div>
        <label class="auth-label" for="d-status"><?php echo t("label_status"); ?></label>
        <input class="auth-input" id="d-status" name="status" type="text" value="Active">
        <label class="auth-label" for="d-email"><?php echo t("label_email"); ?></label>
        <input class="auth-input" id="d-email" name="email" type="email">
        <button class="btn primary auth-button" type="submit"><?php echo t("save"); ?></button>
      </form>
      <form method="post" action="<?php echo htmlspecialchars(url_for($config, "public/delete.php")); ?>" class="drawer-footer">
        <input type="hidden" name="domain" value="">
        <button class="link danger" type="submit" onclick="return confirm('<?php echo t("delete_confirm"); ?>');"><?php echo t("delete_domain"); ?></button>
      </form>
    </div>
    <div class="drawer" data-settings-drawer>
      <div class="drawer-header">
        <div class="drawer-title"><?php echo t("options_title"); ?></div>
        <button class="topbar-action" type="button" data-settings-close><?php echo t("close"); ?></button>
      </div>
      <form method="post" action="<?php echo htmlspecialchars(url_for($config, "public/settings.php")); ?>" class="drawer-section">
        <label class="auth-label" for="s-email-to"><?php echo t("label_email_to"); ?></label>
        <input class="auth-input" id="s-email-to" name="email_to" type="email" value="<?php echo htmlspecialchars($config["email_to"]); ?>">
        <label class="auth-label" for="s-prefix"><?php echo t("label_prefix"); ?></label>
        <input class="auth-input" id="s-prefix" name="mail_subject_prefix" type="text" value="<?php echo htmlspecialchars($config["mail_subject_prefix"]); ?>">
        <div class="field-spacer"></div>
        <button class="btn" type="button" data-test-mail>Tester email</button>
        <div class="auth-hint" data-test-mail-status></div>
        <label class="auth-label" for="s-lang"><?php echo t("label_language"); ?></label>
        <select class="select" id="s-lang" name="language">
          <option value="fr" <?php echo ($config["language"] ?? "fr") === "fr" ? "selected" : ""; ?>>FR</option>
          <option value="en" <?php echo ($config["language"] ?? "fr") === "en" ? "selected" : ""; ?>>EN</option>
        </select>
        <label class="auth-label" for="s-theme"><?php echo t("label_theme"); ?></label>
        <select class="select" id="s-theme" name="theme" data-theme-select>
          <option value="light" <?php echo ($config["theme"] ?? "light") === "light" ? "selected" : ""; ?>><?php echo t("theme_light"); ?></option>
          <option value="dark" <?php echo ($config["theme"] ?? "light") === "dark" ? "selected" : ""; ?>><?php echo t("theme_dark"); ?></option>
          <option value="classic" <?php echo ($config["theme"] ?? "light") === "classic" ? "selected" : ""; ?>><?php echo t("theme_classic"); ?></option>
          <option value="system" <?php echo ($config["theme"] ?? "light") === "system" ? "selected" : ""; ?>><?php echo t("theme_system"); ?></option>
        </select>
        <label class="auth-label"><?php echo t("label_columns"); ?></label>
        <div class="checkbox-grid">
          <label><input type="checkbox" name="columns_visible[]" value="domain" <?php echo $is_visible("domain") ? "checked" : ""; ?>> <?php echo t("table_domain"); ?></label>
          <label><input type="checkbox" name="columns_visible[]" value="registrar" <?php echo $is_visible("registrar") ? "checked" : ""; ?>> <?php echo t("table_registrar"); ?></label>
          <label><input type="checkbox" name="columns_visible[]" value="expiration" <?php echo $is_visible("expiration") ? "checked" : ""; ?>> <?php echo t("table_expiration"); ?></label>
          <label><input type="checkbox" name="columns_visible[]" value="days" <?php echo $is_visible("days") ? "checked" : ""; ?>> <?php echo t("table_days_left"); ?></label>
          <label><input type="checkbox" name="columns_visible[]" value="status" <?php echo $is_visible("status") ? "checked" : ""; ?>> <?php echo t("table_status"); ?></label>
          <label><input type="checkbox" name="columns_visible[]" value="email" <?php echo $is_visible("email") ? "checked" : ""; ?>> <?php echo t("table_email"); ?></label>
          <label><input type="checkbox" name="columns_visible[]" value="project" <?php echo $is_visible("project") ? "checked" : ""; ?>> <?php echo t("table_project"); ?></label>
        </div>
        <label class="auth-label" for="s-days"><?php echo t("label_alert_days"); ?></label>
        <input class="auth-input" id="s-days" name="alert_days" type="text" value="<?php echo htmlspecialchars(implode(", ", $config["alert_days"])); ?>">
        <button class="btn primary auth-button" type="submit"><?php echo t("save_options"); ?></button>
      </form>
      <form method="post" action="<?php echo htmlspecialchars(url_for($config, "public/password.php")); ?>" class="drawer-section account-section">
        <div class="auth-title"><?php echo t("account_title"); ?></div>
        <label class="auth-label" for="p-username"><?php echo t("label_new_username"); ?></label>
        <input class="auth-input" id="p-username" name="new_username" type="text" autocomplete="username" placeholder="<?php echo htmlspecialchars($_SESSION["username"] ?? ""); ?>">
        <label class="auth-label" for="p-current"><?php echo t("label_current_password"); ?></label>
        <input class="auth-input" id="p-current" name="current_password" type="password" autocomplete="current-password" required>
        <label class="auth-label" for="p-new"><?php echo t("label_new_password"); ?></label>
        <input class="auth-input" id="p-new" name="new_password" type="password" autocomplete="new-password" required>
        <label class="auth-label" for="p-confirm"><?php echo t("label_password_confirm"); ?></label>
        <input class="auth-input" id="p-confirm" name="password_confirm" type="password" autocomplete="new-password" required>
        <button class="btn primary auth-button" type="submit"><?php echo t("save"); ?></button>
      </form>
      <form method="post" action="<?php echo htmlspecialchars(url_for($config, "public/delete_account.php")); ?>" class="drawer-section account-section">
        <div class="auth-title"><?php echo t("delete_account_title"); ?></div>
        <label class="auth-label" for="da-current"><?php echo t("label_current_password"); ?></label>
        <input class="auth-input" id="da-current" name="current_password" type="password" autocomplete="current-password" required>
        <button class="btn danger auth-button" type="submit" onclick="return confirm('<?php echo t("delete_account_confirm"); ?>');"><?php echo t("delete_account_button"); ?></button>
      </form>
    </div>
    <script>
      window.I18N = {
        autofill_enter_domain: "<?php echo htmlspecialchars(t("autofill_enter_domain")); ?>",
        autofill_lookup: "<?php echo htmlspecialchars(t("autofill_lookup")); ?>",
        autofill_done: "<?php echo htmlspecialchars(t("autofill_done")); ?>",
        autofill_failed: "<?php echo htmlspecialchars(t("autofill_failed")); ?>",
        delete_confirm: "<?php echo htmlspecialchars(t("delete_confirm")); ?>",
        expiring_badge: "<?php echo htmlspecialchars(t("expiring_badge")); ?>"
      };
    </script>
    <div class="shortcuts" data-shortcuts>
      <div class="shortcuts-title"><?php echo t("shortcuts_title"); ?></div>
      <div class="shortcuts-grid">
        <div class="shortcut"><kbd>A</kbd><span><?php echo t("shortcut_add"); ?></span></div>
        <div class="shortcut"><kbd>O</kbd><span><?php echo t("shortcut_options"); ?></span></div>
        <div class="shortcut"><kbd>/</kbd><span><?php echo t("shortcut_search"); ?></span></div>
        <div class="shortcut"><kbd>?</kbd><span><?php echo t("shortcut_help"); ?></span></div>
        <div class="shortcut"><kbd>Esc</kbd><span><?php echo t("shortcut_close"); ?></span></div>
        <div class="shortcut"><kbd>↑/↓</kbd><span><?php echo t("shortcut_nav"); ?></span></div>
        <div class="shortcut"><kbd>Enter</kbd><span><?php echo t("shortcut_edit"); ?></span></div>
        <div class="shortcut"><kbd>Del</kbd><span><?php echo t("shortcut_delete"); ?></span></div>
      </div>
      <button class="topbar-action" type="button" data-shortcuts-close><?php echo t("close"); ?></button>
    </div>
    <div class="version">v<?php echo htmlspecialchars($config["version"] ?? ""); ?></div>
    <script>window.BASE_URL = "<?php echo htmlspecialchars(base_url($config)); ?>";</script>
    <script src="<?php echo htmlspecialchars(url_for($config, "public/assets/app.js")); ?>?v=<?php echo urlencode($config["version"] ?? ""); ?>"></script>
  </body>
</html>
