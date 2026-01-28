<?php
function get_db(array $config): PDO {
    $db = new PDO("sqlite:" . $config["db_path"]);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec("PRAGMA foreign_keys = ON");

    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS settings (
            user_id INTEGER,
            key TEXT NOT NULL,
            value TEXT NOT NULL,
            UNIQUE(user_id, key)
        );
        CREATE TABLE IF NOT EXISTS domains (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            domain TEXT NOT NULL UNIQUE,
            project TEXT,
            registrar TEXT,
            expires TEXT,
            status TEXT,
            email TEXT
        );
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            domain_id INTEGER NOT NULL,
            days INTEGER NOT NULL,
            date TEXT NOT NULL,
            UNIQUE(domain_id, days, date),
            FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE
        );
    ");

    // Ensure legacy tables have user_id
    $cols = $db->query("PRAGMA table_info(domains)")->fetchAll();
    $has_user_id = false;
    foreach ($cols as $c) {
        if (($c["name"] ?? "") === "user_id") {
            $has_user_id = true;
            break;
        }
    }
    if (!$has_user_id) {
        $db->exec("ALTER TABLE domains ADD COLUMN user_id INTEGER");
    }

    // Ensure unique per user for domains (migrate if needed)
    $indexes = $db->query("PRAGMA index_list(domains)")->fetchAll();
    $has_unique_user_domain = false;
    foreach ($indexes as $idx) {
        if (!empty($idx["unique"])) {
            $info = $db->query("PRAGMA index_info(" . $idx["name"] . ")")->fetchAll();
            $cols = array_map(function($r) { return $r["name"]; }, $info);
            if ($cols === ["user_id", "domain"] || $cols === ["domain", "user_id"]) {
                $has_unique_user_domain = true;
                break;
            }
        }
    }
    if (!$has_unique_user_domain) {
        $db->exec("
            CREATE TABLE domains_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                domain TEXT NOT NULL,
                project TEXT,
                registrar TEXT,
                expires TEXT,
                status TEXT,
                email TEXT,
                UNIQUE(user_id, domain)
            );
        ");
        $first_user = (int)$db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
        $db->exec("INSERT INTO domains_new (id, user_id, domain, project, registrar, expires, status, email)
                   SELECT id, COALESCE(user_id, {$first_user}), domain, project, registrar, expires, status, email FROM domains");
        $db->exec("DROP TABLE domains");
        $db->exec("ALTER TABLE domains_new RENAME TO domains");
    }

    // Migrate settings to per-user if needed
    $scols = $db->query("PRAGMA table_info(settings)")->fetchAll();
    $settings_has_user_id = false;
    foreach ($scols as $c) {
        if (($c["name"] ?? "") === "user_id") {
            $settings_has_user_id = true;
            break;
        }
    }
    if (!$settings_has_user_id) {
        $db->exec("
            CREATE TABLE settings_new (
                user_id INTEGER,
                key TEXT NOT NULL,
                value TEXT NOT NULL,
                UNIQUE(user_id, key)
            );
        ");
        $first_user = (int)$db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
        $db->exec("INSERT INTO settings_new (user_id, key, value)
                   SELECT {$first_user}, key, value FROM settings");
        $db->exec("DROP TABLE settings");
        $db->exec("ALTER TABLE settings_new RENAME TO settings");
    }

    // No default admin user creation.

    return $db;
}

function apply_settings(PDO $db, array $config, ?int $user_id = null): array {
    $defaults = [
        "site_name" => $config["site_name"],
        "email_to" => $config["email_to"],
        "mail_subject_prefix" => $config["mail_subject_prefix"],
        "alert_days" => $config["alert_days"],
        "language" => $config["language"] ?? "fr",
        "theme" => $config["theme"] ?? "light",
        "columns_visible" => json_encode(["domain", "registrar", "expiration", "days", "status", "email", "project"]),
    ];

    if ($user_id === null) {
        $rows = [];
    } else {
        $stmt = $db->prepare("SELECT key, value FROM settings WHERE user_id = :uid");
        $stmt->execute([":uid" => $user_id]);
        $rows = $stmt->fetchAll();
    }
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row["key"]] = $row["value"];
    }

    foreach ($defaults as $key => $value) {
        if ($user_id !== null && !array_key_exists($key, $settings)) {
            $stmt = $db->prepare("INSERT OR IGNORE INTO settings (user_id, key, value) VALUES (:uid, :k, :v)");
            $stmt->execute([
                ":uid" => $user_id,
                ":k" => $key,
                ":v" => $key === "alert_days" ? json_encode($value) : (string)$value,
            ]);
            $settings[$key] = $key === "alert_days" ? json_encode($value) : (string)$value;
        }
    }

    $config["site_name"] = $settings["site_name"] ?? $config["site_name"];
    $config["email_to"] = $settings["email_to"] ?? $config["email_to"];
    $config["mail_subject_prefix"] = $settings["mail_subject_prefix"] ?? $config["mail_subject_prefix"];
    $config["language"] = $settings["language"] ?? ($config["language"] ?? "fr");
    $config["theme"] = $settings["theme"] ?? ($config["theme"] ?? "light");
    if (!empty($settings["alert_days"])) {
        $decoded = json_decode($settings["alert_days"], true);
        if (is_array($decoded)) {
            $config["alert_days"] = array_values(array_map("intval", $decoded));
        }
    }
    if (!empty($settings["columns_visible"])) {
        $decoded = json_decode($settings["columns_visible"], true);
        if (is_array($decoded)) {
            $config["columns_visible"] = $decoded;
        }
    }

    return $config;
}
