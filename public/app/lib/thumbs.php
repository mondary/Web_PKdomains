<?php

function thumb_slug(string $domain): string {
    return preg_replace("/[^a-z0-9]+/", "-", strtolower($domain));
}

function thumb_paths(string $domain): array {
    $slug = thumb_slug($domain);
    $dir = __DIR__ . "/../../thumbs";
    return [
        "dir" => $dir,
        "png" => $dir . "/" . $slug . ".png",
        "public_png" => "public/thumbs/" . $slug . ".png",
    ];
}

function get_cached_thumbnail(string $domain): ?string {
    $domain = trim($domain);
    if ($domain === "") return null;
    $paths = thumb_paths($domain);
    if (is_file($paths["png"])) {
        $header = @file_get_contents($paths["png"], false, null, 0, 6);
        if ($header && substr($header, 0, 4) === "\x89PNG") {
            return $paths["public_png"];
        }
        // Not a PNG (likely GIF placeholder) -> remove
        @unlink($paths["png"]);
    }
    return null;
}

function cache_site_thumbnail(string $domain, bool $force = false): ?string {
    $domain = trim($domain);
    if ($domain === "") return null;
    $paths = thumb_paths($domain);

    // Use cached thumbnail if available
    if (is_file($paths["png"]) && !$force) {
        return $paths["public_png"];
    }

    if (!is_dir($paths["dir"])) {
        @mkdir($paths["dir"], 0775, true);
    }

    $context = stream_context_create([
        "http" => [
            "timeout" => 15,
            "user_agent" => "DomainManager-Thumb/1.0",
        ],
    ]);

    $urls = [
        "https://s.wordpress.com/mshots/v1/" . urlencode("https://" . $domain) . "?w=600",
        "https://image.thum.io/get/width/600/https://" . $domain,
    ];
    foreach ($urls as $url) {
        $raw = @file_get_contents($url, false, $context);
        if ($raw && strlen($raw) > 1000 && substr($raw, 0, 4) === "\x89PNG") {
            @file_put_contents($paths["png"], $raw);
            return $paths["public_png"];
        }
    }
    return null;
}

function favicon_paths(string $domain): array {
    $slug = thumb_slug($domain);
    $dir = __DIR__ . "/../../favicons";
    return [
        "dir" => $dir,
        "ico" => $dir . "/" . $slug . ".ico",
        "png" => $dir . "/" . $slug . ".png",
        "public_ico" => "public/favicons/" . $slug . ".ico",
        "public_png" => "public/favicons/" . $slug . ".png",
    ];
}

function cache_favicon(string $domain): ?string {
    $domain = trim($domain);
    if ($domain === "") return null;
    $paths = favicon_paths($domain);
    if (is_file($paths["png"])) return $paths["public_png"];
    if (is_file($paths["ico"])) return $paths["public_ico"];
    if (!is_dir($paths["dir"])) {
        @mkdir($paths["dir"], 0775, true);
    }
    $context = stream_context_create([
        "http" => [
            "timeout" => 5,
            "user_agent" => "DomainManager-Favicon/1.0",
            "follow_location" => 1,
            "max_redirects" => 3,
        ],
    ]);
    $hosts = [$domain];
    $final = resolve_final_host($domain, $context);
    if ($final && !in_array($final, $hosts, true)) {
        $hosts[] = $final;
    }
    foreach ($hosts as $host) {
        $remote_ico = "https://icons.duckduckgo.com/ip3/" . $host . ".ico";
        $raw = @file_get_contents($remote_ico, false, $context);
        if ($raw) {
            @file_put_contents($paths["ico"], $raw);
            return $paths["public_ico"];
        }
        $remote_png = "https://www.google.com/s2/favicons?domain=" . urlencode($host) . "&sz=64";
        $raw = @file_get_contents($remote_png, false, $context);
        if ($raw) {
            @file_put_contents($paths["png"], $raw);
            return $paths["public_png"];
        }
    }
    return null;
}

function resolve_final_host(string $domain, $context): ?string {
    $url = "https://" . $domain;
    $headers = @get_headers($url, true, $context);
    if (!$headers) return null;
    $location = $headers["Location"] ?? null;
    if (is_array($location)) {
        $location = end($location);
    }
    if (!$location) return null;
    $parts = parse_url($location);
    if (!$parts || empty($parts["host"])) return null;
    return $parts["host"];
}
