<?php

function detect_base_url(): string {
    $script = $_SERVER["SCRIPT_NAME"] ?? "";
    if ($script === "") return "";
    // If app is served from /.../index.php or /.../public/index.php, base is the directory
    $dir = rtrim(str_replace("\\", "/", dirname($script)), "/");
    if ($dir === ".") return "";
    // If script is in /public/, base is parent of /public
    if (substr($dir, -7) === "/public") {
        $dir = substr($dir, 0, -7);
    }
    return $dir === "" ? "" : $dir;
}

function base_url(array $config): string {
    $base = isset($config["base_url"]) ? trim((string)$config["base_url"]) : "";
    $base = rtrim($base, "/");
    if ($base === "") {
        $base = detect_base_url();
    }
    return $base === "" ? "" : $base;
}

function url_for(array $config, string $path): string {
    $base = base_url($config);
    $path = ltrim($path, "/");
    if ($path === "") {
        return $base !== "" ? $base . "/" : "/";
    }
    return ($base !== "" ? $base : "") . "/" . $path;
}
