<?php

function find_user(PDO $db, string $username): ?array {
    $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = :u");
    $stmt->execute([":u" => $username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function create_user(PDO $db, string $username, string $password): bool {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, password_hash, created_at) VALUES (:u, :p, :c)");
    return $stmt->execute([
        ":u" => $username,
        ":p" => $hash,
        ":c" => (new DateTime())->format("Y-m-d H:i:s"),
    ]);
}

function update_user_password(PDO $db, int $user_id, string $password): bool {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password_hash = :p WHERE id = :id");
    return $stmt->execute([":p" => $hash, ":id" => $user_id]);
}

function update_username(PDO $db, int $user_id, string $username): bool {
    $stmt = $db->prepare("UPDATE users SET username = :u WHERE id = :id");
    return $stmt->execute([":u" => $username, ":id" => $user_id]);
}
