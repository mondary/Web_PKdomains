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
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS domains (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
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

    $count = (int)$db->query("SELECT COUNT(*) AS c FROM users")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash($config["default_password"], PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, created_at) VALUES (:u, :p, :c)");
        $stmt->execute([
            ":u" => $config["default_username"],
            ":p" => $hash,
            ":c" => (new DateTime())->format("Y-m-d H:i:s"),
        ]);
    }

    return $db;
}

function apply_settings(PDO $db, array $config): array {
    $defaults = [
        "site_name" => $config["site_name"],
        "email_to" => $config["email_to"],
        "email_from" => $config["email_from"],
        "mail_subject_prefix" => $config["mail_subject_prefix"],
        "alert_days" => $config["alert_days"],
        "language" => $config["language"] ?? "fr",
        "columns_visible" => json_encode(["domain", "registrar", "expiration", "days", "status", "email", "project"]),
    ];

    $rows = $db->query("SELECT key, value FROM settings")->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row["key"]] = $row["value"];
    }

    foreach ($defaults as $key => $value) {
        if (!array_key_exists($key, $settings)) {
            $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (:k, :v)");
            $stmt->execute([
                ":k" => $key,
                ":v" => $key === "alert_days" ? json_encode($value) : (string)$value,
            ]);
            $settings[$key] = $key === "alert_days" ? json_encode($value) : (string)$value;
        }
    }

    $config["site_name"] = $settings["site_name"] ?? $config["site_name"];
    $config["email_to"] = $settings["email_to"] ?? $config["email_to"];
    $config["email_from"] = $settings["email_from"] ?? $config["email_from"];
    $config["mail_subject_prefix"] = $settings["mail_subject_prefix"] ?? $config["mail_subject_prefix"];
    $config["language"] = $settings["language"] ?? ($config["language"] ?? "fr");
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
