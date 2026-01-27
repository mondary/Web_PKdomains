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
