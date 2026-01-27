<?php
$config = require __DIR__ . "/config.php";
date_default_timezone_set($config["timezone"]);

$domains = [];
if (is_file($config["data_domains"])) {
    $json = file_get_contents($config["data_domains"]);
    $domains = json_decode($json, true) ?: [];
}

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
        <button class="btn primary">Add domain</button>
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
                <td class="muted"><?php echo htmlspecialchars($d["email"] ?? $config["email_to"]); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="footer">
        Alert thresholds: <?php echo implode(", ", $config["alert_days"]); ?> days.
      </div>
    </div>
    <script src="assets/app.js"></script>
  </body>
</html>
