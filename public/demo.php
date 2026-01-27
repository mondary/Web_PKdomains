<?php
if (!isset($config)) {
    $config = require __DIR__ . "/config.php";
}
require_once __DIR__ . "/app/lib/i18n.php";
require_once __DIR__ . "/app/lib/logos.php";
date_default_timezone_set($config["timezone"]);
$GLOBALS["i18n"] = i18n_load($config["language"] ?? "fr");

if (!function_exists("days_until")) {
    function days_until(string $date): ?int {
        $today = new DateTime("today");
        $exp = DateTime::createFromFormat("Y-m-d", $date);
        if (!$exp) {
            return null;
        }
        $diff = $today->diff($exp);
        return (int)$diff->format("%r%a");
    }
}

if (!function_exists("status_class")) {
    function status_class(?int $days): string {
        if ($days === null) return "unknown";
        if ($days <= 7) return "danger";
        if ($days <= 30) return "warn";
        return "ok";
    }
}

if (!function_exists("registrar_logo_url")) {
    function registrar_logo_url(string $registrar): ?string {
        $domain = registrar_domain_for_logo($registrar);
        if (!$domain) return null;
        return "https://logo.clearbit.com/" . $domain;
    }
}

if (!function_exists("registrar_site_url")) {
    function registrar_site_url(string $registrar): ?string {
        $r = strtolower(trim($registrar));
        foreach (registrar_url_map() as $key => $url) {
            if (strpos($r, $key) !== false) {
                return $url;
            }
        }
        $domain = registrar_domain_for_logo($registrar);
        if (!$domain) return null;
        return "https://www." . $domain . "/";
    }
}

$today = new DateTime("today");
$demo_items = [
    ["domain" => "atelier-lys.fr", "project" => "Portfolio", "registrar" => "OVH", "days" => 142, "email" => "hello@atelier-lys.fr"],
    ["domain" => "cafe-marin.fr", "project" => "Resto", "registrar" => "Gandi", "days" => 19, "email" => "contact@cafe-marin.fr"],
    ["domain" => "studio-echo.com", "project" => "Agence", "registrar" => "Namecheap", "days" => 61, "email" => "team@studio-echo.com"],
    ["domain" => "lavande.app", "project" => "SaaS", "registrar" => "Cloudflare", "days" => 6, "email" => "ops@lavande.app"],
    ["domain" => "marble.dev", "project" => "Side project", "registrar" => "Porkbun", "days" => 210, "email" => "founder@marble.dev"],
    ["domain" => "voyage-nordic.fr", "project" => "Blog", "registrar" => "Squarespace", "days" => 28, "email" => "contact@voyage-nordic.fr"],
];

$domains = [];
foreach ($demo_items as $item) {
    $exp = (clone $today)->modify(($item["days"] >= 0 ? "+" : "") . $item["days"] . " days");
    $item["expires"] = $exp->format("Y-m-d");
    $domains[] = $item;
}

$visible_cols = ["domain", "registrar", "expiration", "days", "status", "email", "project"];
$is_visible = function (string $key) use ($visible_cols): bool {
    return in_array($key, $visible_cols, true);
};

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
    <title><?php echo htmlspecialchars($config["site_name"]); ?> - Démo</title>
    <link rel="stylesheet" href="/public/assets/style.css">
  </head>
  <body>
    <div class="topbar">
      <div class="topbar-inner">
        <div class="brand"><?php echo htmlspecialchars($config["site_name"]); ?></div>
        <span class="badge demo-badge">Démo</span>
        <div class="spacer"></div>
        <button class="topbar-action" type="button" data-login-open>Se connecter</button>
      </div>
    </div>

    <div class="container">
      <div class="header">
        <div>
          <h1>Suivi de domaines</h1>
          <div class="hint">Vue d’ensemble, expiration, registrar et statut en un coup d’œil.</div>
        </div>
      </div>
      <div class="demo-note">Mode démo — connectez-vous pour gérer vos domaines.</div>

      <div class="controls">
        <div class="search">
          <span class="muted">🔎</span>
          <input type="text" placeholder="<?php echo t("search_placeholder"); ?>" disabled>
        </div>
        <select class="select" disabled>
          <option selected><?php echo t("filter_all"); ?></option>
          <option>&lt; 30j</option>
          <option>&lt; 60j</option>
          <option>&lt; 90j</option>
          <option>&lt; 180j</option>
        </select>
        <button class="btn primary" type="button" data-login-open><?php echo t("add_domain"); ?></button>
      </div>

      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th class="<?php echo $is_visible("domain") ? "" : "col-hidden"; ?>" data-col="domain"><?php echo t("table_domain"); ?> (<?php echo $total; ?>)</th>
              <th class="<?php echo $is_visible("registrar") ? "" : "col-hidden"; ?>" data-col="registrar"><?php echo t("table_registrar"); ?></th>
              <th class="<?php echo $is_visible("expiration") ? "" : "col-hidden"; ?>" data-col="expiration"><?php echo t("table_expiration"); ?></th>
              <th class="<?php echo $is_visible("days") ? "" : "col-hidden"; ?>" data-col="days"><?php echo t("table_days_left"); ?></th>
              <th class="<?php echo $is_visible("status") ? "" : "col-hidden"; ?>" data-col="status"><?php echo t("table_status"); ?></th>
              <th class="<?php echo $is_visible("email") ? "" : "col-hidden"; ?>" data-col="email"><?php echo t("table_email"); ?></th>
              <th class="<?php echo $is_visible("project") ? "" : "col-hidden"; ?>" data-col="project"><?php echo t("table_project"); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($domains as $d): ?>
              <?php $days = days_until($d["expires"] ?? ""); ?>
              <tr>
                <td class="domain-cell <?php echo $is_visible("domain") ? "" : "col-hidden"; ?>" data-col="domain" data-label="<?php echo t("table_domain"); ?>">
                  <span class="domain-media">
                    <span class="thumb placeholder"></span>
                    <span class="favicon placeholder"></span>
                  </span>
                  <span class="link"><?php echo htmlspecialchars($d["domain"] ?? ""); ?></span>
                </td>
                <td class="<?php echo $is_visible("registrar") ? "" : "col-hidden"; ?>" data-col="registrar" data-label="<?php echo t("table_registrar"); ?>">
                  <div class="registrar">
                    <?php $logo = registrar_logo_url($d["registrar"] ?? ""); ?>
                    <?php $reg_url = registrar_site_url($d["registrar"] ?? ""); ?>
                    <?php if ($logo): ?>
                      <img class="registrar-logo" src="<?php echo htmlspecialchars($logo); ?>" alt="" referrerpolicy="no-referrer">
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
                <td class="muted <?php echo $is_visible("email") ? "" : "col-hidden"; ?>" data-col="email" data-label="<?php echo t("table_email"); ?>"><?php echo htmlspecialchars($d["email"] ?? ""); ?></td>
                <td class="muted <?php echo $is_visible("project") ? "" : "col-hidden"; ?>" data-col="project" data-label="<?php echo t("table_project"); ?>"><?php echo htmlspecialchars($d["project"] ?? ""); ?></td>
                <td class="actions" data-label="<?php echo t("edit"); ?>">
                  <button class="icon-button" type="button" data-login-open title="Se connecter" aria-label="Se connecter">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" aria-hidden="true" focusable="false"><path d="M416.9 85.2L372 130.1L509.9 268L554.8 223.1C568.4 209.6 576 191.2 576 172C576 152.8 568.4 134.4 554.8 120.9L519.1 85.2C505.6 71.6 487.2 64 468 64C448.8 64 430.4 71.6 416.9 85.2zM338.1 164L122.9 379.1C112.2 389.8 104.4 403.2 100.3 417.8L64.9 545.6C62.6 553.9 64.9 562.9 71.1 569C77.3 575.1 86.2 577.5 94.5 575.2L222.3 539.7C236.9 535.6 250.2 527.9 261 517.1L476 301.9L338.1 164z"/></svg>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="toast demo-toast"><?php echo t("expiring_badge", ["count" => $expiring, "threshold" => 30]); ?></div>

    <div class="drawer-backdrop open" data-demo-backdrop></div>
    <div class="drawer demo-drawer open" data-demo-drawer>
      <div class="drawer-header">
        <div class="drawer-title"><?php echo t("login_title"); ?></div>
        <button class="topbar-action" type="button" data-login-close><?php echo t("close"); ?></button>
      </div>
      <form method="post" action="/public/login.php" class="drawer-body">
        <label class="auth-label" for="demo-username"><?php echo t("label_username"); ?></label>
        <input class="auth-input" id="demo-username" name="username" type="text" autocomplete="username" required>
        <label class="auth-label" for="demo-password"><?php echo t("label_password"); ?></label>
        <input class="auth-input" id="demo-password" name="password" type="password" autocomplete="current-password" required>
        <button class="btn primary auth-button" type="submit"><?php echo t("login_button"); ?></button>
        <div class="auth-links">
          <a class="link" href="/public/register.php"><?php echo t("register_link"); ?></a>
        </div>
      </form>
    </div>

    <button class="demo-cta" type="button" data-login-open>Se connecter</button>

    <script>
      (function () {
        const drawer = document.querySelector("[data-demo-drawer]");
        const backdrop = document.querySelector("[data-demo-backdrop]");
        const openButtons = document.querySelectorAll("[data-login-open]");
        const closeButtons = document.querySelectorAll("[data-login-close]");

        const open = () => {
          if (!drawer || !backdrop) return;
          drawer.classList.add("open");
          backdrop.classList.add("open");
          const user = drawer.querySelector("#demo-username");
          if (user) setTimeout(() => user.focus(), 0);
        };

        const close = () => {
          if (!drawer || !backdrop) return;
          drawer.classList.remove("open");
          backdrop.classList.remove("open");
        };

        openButtons.forEach((btn) => btn.addEventListener("click", open));
        closeButtons.forEach((btn) => btn.addEventListener("click", close));
        if (backdrop) backdrop.addEventListener("click", close);
        document.addEventListener("keydown", (e) => {
          if (e.key === "Escape") close();
        });
        open();
      })();
    </script>
  </body>
</html>
