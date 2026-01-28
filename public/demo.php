<?php
if (!isset($config)) {
    $config = require __DIR__ . "/config.php";
}
require_once __DIR__ . "/app/lib/url.php";
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
    ["display" => "jsoncrack.com", "project" => "SaaS", "registrar" => "Cloudflare", "days" => 92, "email" => "hello@jsoncrack.com", "thumb" => "public/thumbs/jsoncrack-com.png", "favicon" => "public/favicons/jsoncrack-com.ico", "registrar_logo" => "public/logos/cloudflare-com.ico"],
    ["display" => "pocketbase.io", "project" => "Backend", "registrar" => "Cloudflare", "days" => 75, "email" => "hello@pocketbase.io", "thumb" => "public/thumbs/pocketbase-io.png", "favicon" => "public/favicons/pocketbase-io.ico", "registrar_logo" => "public/logos/cloudflare-com.ico"],
    ["display" => "nhost.io", "project" => "BaaS", "registrar" => "Cloudflare", "days" => 104, "email" => "hello@nhost.io", "thumb" => "public/thumbs/nhost-io.png", "favicon" => "public/favicons/nhost-io.ico", "registrar_logo" => "public/logos/cloudflare-com.ico"],
    ["display" => "doublememory.com", "project" => "Agency", "registrar" => "OVH", "days" => 34, "email" => "contact@doublememory.com", "thumb" => "public/thumbs/doublememory-com.png", "favicon" => "public/favicons/doublememory-com.ico", "registrar_logo" => "public/logos/ovh-com.ico"],
    ["display" => "hub.pouark.com", "project" => "App", "registrar" => "OVH", "days" => 21, "email" => "hello@pouark.com", "thumb" => "public/thumbs/hub-pouark-com.png", "favicon" => "public/favicons/pouark-com.ico", "registrar_logo" => "public/logos/ovh-com.ico"],
    ["display" => "mondary.me", "project" => "Portfolio", "registrar" => "OVH", "days" => 14, "email" => "contact@mondary.me", "thumb" => "public/thumbs/mondary-me.png", "favicon" => "public/assets/demo-favicon.svg", "registrar_logo" => "public/logos/ovh-com.ico"],
    ["display" => "gomining.com", "project" => "Crypto", "registrar" => "Namecheap", "days" => 48, "email" => "support@gomining.com", "thumb" => "public/thumbs/gomining-com.png", "favicon" => "public/favicons/gomining-com.ico", "registrar_logo" => "public/logos/namecheap-com.ico"],
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
    <title>PK domain manager</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(url_for($config, "public/assets/style.css")); ?>?v=<?php echo urlencode($config["version"] ?? ""); ?>">
  </head>
  <body>
    <div class="watermark" style="background-image:url('<?php echo htmlspecialchars(url_for($config, "icon.png")); ?>');"></div>
    <div class="topbar">
      <div class="topbar-inner">
        <div class="brand">
          <img class="brand-logo" src="<?php echo htmlspecialchars(url_for($config, "icon.png")); ?>" alt="">
          <span><?php echo htmlspecialchars($config["site_name"]); ?></span>
        </div>
        <button class="burger" type="button" aria-label="Menu" data-burger-open>☰</button>
        <div class="spacer"></div>
        <button class="topbar-action" type="button" data-login-open>Se connecter</button>
        <div class="topbar-menu" data-burger-menu>
          <button class="topbar-menu-item" type="button" data-login-open>Se connecter</button>
        </div>
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
                    <img class="thumb" src="<?php echo htmlspecialchars(url_for($config, $d["thumb"] ?? "public/assets/demo-thumb.svg")); ?>" alt="">
                  </span>
                  <span class="domain-title">
                    <img class="favicon" src="<?php echo htmlspecialchars(url_for($config, $d["favicon"] ?? "public/assets/demo-favicon.svg")); ?>" alt="">
                    <span class="link"><?php echo htmlspecialchars($d["display"] ?? ""); ?></span>
                  </span>
                  <span class="domain-registrar-line">
                    <img class="registrar-logo" src="<?php echo htmlspecialchars(url_for($config, $d["registrar_logo"] ?? "public/assets/demo-registrar.svg")); ?>" alt="">
                    <span><?php echo htmlspecialchars($d["registrar"] ?? ""); ?></span>
                  </span>
                </td>
                <td class="<?php echo $is_visible("registrar") ? "" : "col-hidden"; ?>" data-col="registrar" data-label="<?php echo t("table_registrar"); ?>">
                  <div class="registrar">
                    <img class="registrar-logo" src="<?php echo htmlspecialchars(url_for($config, $d["registrar_logo"] ?? "public/assets/demo-registrar.svg")); ?>" alt="">
                    <span><?php echo htmlspecialchars($d["registrar"] ?? ""); ?></span>
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
                    <span class="edit-label">Éditer</span>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>


    <div class="drawer-backdrop open" data-demo-backdrop></div>
    <div class="drawer demo-drawer open" data-demo-drawer>
      <div class="drawer-header">
        <div class="drawer-title"><?php echo t("login_title"); ?></div>
        <button class="topbar-action" type="button" data-login-close><?php echo t("close"); ?></button>
      </div>
      <form method="post" action="<?php echo htmlspecialchars(url_for($config, "public/login.php")); ?>" class="drawer-body">
        <label class="auth-label" for="demo-username"><?php echo t("label_username"); ?></label>
        <input class="auth-input" id="demo-username" name="username" type="text" autocomplete="username" required>
        <label class="auth-label" for="demo-password"><?php echo t("label_password"); ?></label>
        <input class="auth-input" id="demo-password" name="password" type="password" autocomplete="current-password" required>
        <button class="btn primary auth-button" type="submit"><?php echo t("login_button"); ?></button>
        <div class="auth-links">
          <a class="link" href="<?php echo htmlspecialchars(url_for($config, "public/register.php")); ?>"><?php echo t("register_link"); ?></a>
        </div>
      </form>
    </div>

    <script>window.BASE_URL = "<?php echo htmlspecialchars(base_url($config)); ?>";</script>
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
