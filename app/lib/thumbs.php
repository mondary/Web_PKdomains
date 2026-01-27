<?php

function thumb_slug(string $domain): string {
    return preg_replace("/[^a-z0-9]+/", "-", strtolower($domain));
}

function thumb_paths(string $domain): array {
    $slug = thumb_slug($domain);
    $dir = __DIR__ . "/../../public/thumbs";
    return [
        "dir" => $dir,
        "png" => $dir . "/" . $slug . ".png",
        "public_png" => "thumbs/" . $slug . ".png",
    ];
}

function cache_site_thumbnail(string $domain): ?string {
    $domain = trim($domain);
    if ($domain === "") return null;
    $paths = thumb_paths($domain);
    if (is_file($paths["png"])) return $paths["public_png"];
    if (!is_dir($paths["dir"])) {
        @mkdir($paths["dir"], 0775, true);
    }
    $url = "https://s.wordpress.com/mshots/v1/" . urlencode("https://" . $domain) . "?w=600";
    $context = stream_context_create([
        "http" => [
            "timeout" => 10,
            "user_agent" => "DomainManager-Thumb/1.0",
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if ($raw) {
        @file_put_contents($paths["png"], $raw);
        return $paths["public_png"];
    }
    return null;
}
