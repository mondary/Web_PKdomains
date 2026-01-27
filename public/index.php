<?php
$config = require __DIR__ . "/../config.php";
require_once __DIR__ . "/../app/lib/auth.php";
require_once __DIR__ . "/../app/lib/db.php";
date_default_timezone_set($config["timezone"]);
require_login($config);

$db = get_db($config);
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

$total = count($domains);
$expiring = 0;
foreach ($domains as $d) {
    $days = days_until($d["expires"] ?? "");
    if ($days !== null && $days <= 30) $expiring++;
}
?>
<!doctype html>
<html lang="fr">
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
        <div class="nav">
          <span class="active">Domains</span>
          <span>Registrars</span>
          <span>Projects</span>
          <span>Notifications</span>
        </div>
        <div class="spacer"></div>
        <a class="link" href="logout.php">Logout</a>
        <div class="badge"><?php echo $expiring; ?> expiring ≤ 30j</div>
      </div>
    </div>

    <div class="container">
      <div class="header">
        <h1>Domains</h1>
        <div class="hint">Alerts email sur l'expiration des domaines.</div>
      </div>

      <div class="controls">
        <div class="search">
          <span class="muted">🔎</span>
          <input data-search type="text" placeholder="Search domains by name or registrar">
        </div>
        <select class="select">
          <option>Filter projects</option>
        </select>
        <button class="btn primary" type="button" data-drawer-open="add">Add domain</button>
      </div>

      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th>Domain</th>
              <th>Project</th>
              <th>Registrar</th>
              <th>Expiration</th>
              <th>Days left</th>
              <th>Status</th>
              <th>Email</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($domains as $d): ?>
              <?php $days = days_until($d["expires"] ?? ""); ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($d["domain"] ?? ""); ?></strong></td>
                <td class="muted"><?php echo htmlspecialchars($d["project"] ?? ""); ?></td>
                <td><?php echo htmlspecialchars($d["registrar"] ?? ""); ?></td>
                <td><?php echo htmlspecialchars($d["expires"] ?? ""); ?></td>
                <td><?php echo $days === null ? "—" : $days; ?></td>
                <td><span class="pill <?php echo status_class($days); ?>">
                  <?php
                    if ($days === null) echo "Unknown";
                    elseif ($days <= 7) echo "Critical";
                    elseif ($days <= 30) echo "Soon";
                    else echo "OK";
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
                  >Edit</button>
                  <form method="post" action="delete.php" class="inline">
                    <input type="hidden" name="domain" value="<?php echo htmlspecialchars($d["domain"] ?? ""); ?>">
                    <button class="link danger" type="submit" onclick="return confirm('Delete this domain?');">Delete</button>
                  </form>
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
        <div class="drawer-title" data-drawer-title>Add domain</div>
        <button class="link" type="button" data-drawer-close>Close</button>
      </div>
      <form method="post" action="save.php" class="drawer-body">
        <input type="hidden" name="original_domain" value="">
        <label class="auth-label" for="d-domain">Domain *</label>
        <input class="auth-input" id="d-domain" name="domain" type="text" required>
        <button class="btn auth-button" type="button" data-autofill>Auto-fill registrar &amp; expiration</button>
        <div class="auth-hint" data-autofill-status></div>
        <label class="auth-label" for="d-project">Project</label>
        <input class="auth-input" id="d-project" name="project" type="text">
        <label class="auth-label" for="d-registrar">Registrar</label>
        <input class="auth-input" id="d-registrar" name="registrar" type="text" data-field-registrar>
        <label class="auth-label" for="d-expires">Expiration (YYYY-MM-DD)</label>
        <input class="auth-input" id="d-expires" name="expires" type="text" placeholder="2026-12-31" data-field-expires>
        <label class="auth-label" for="d-status">Status</label>
        <input class="auth-input" id="d-status" name="status" type="text" value="Active">
        <label class="auth-label" for="d-email">Alert email (optional)</label>
        <input class="auth-input" id="d-email" name="email" type="email">
        <button class="btn primary auth-button" type="submit">Save</button>
      </form>
    </div>
    <script src="assets/app.js"></script>
  </body>
</html>
