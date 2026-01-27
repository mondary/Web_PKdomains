<?php

function registrar_domain_map(): array {
    return [
        "ovh" => "ovh.com",
        "ovh sas" => "ovh.com",
        "spaceship" => "spaceship.com",
        "spaceship, inc." => "spaceship.com",
        "squarespace" => "squarespace.com",
        "porkbun" => "porkbun.com",
        "key-systems" => "key-systems.net",
        "key-systems gmbh" => "key-systems.net",
        "namecheap" => "namecheap.com",
        "godaddy" => "godaddy.com",
        "gandi" => "gandi.net",
        "cloudflare" => "cloudflare.com",
    ];
}

function registrar_url_map(): array {
    return [
        "spaceship" => "https://www.spaceship.com/application/domain-list-application/",
        "spaceship, inc." => "https://www.spaceship.com/application/domain-list-application/",
        "squarespace" => "https://account.squarespace.com/domains",
        "porkbun" => "https://porkbun.com/account/domainsSpeedy",
        "ovh" => "https://manager.eu.ovhcloud.com/#/web-domains/domain",
        "ovh sas" => "https://manager.eu.ovhcloud.com/#/web-domains/domain",
    ];
}

function registrar_domain_for_logo(string $registrar): ?string {
    $r = strtolower(trim($registrar));
    if ($r === "") return null;
    foreach (registrar_domain_map() as $key => $domain) {
        if (strpos($r, $key) !== false) {
            return $domain;
        }
    }
    return null;
}

function logo_slug(string $domain): string {
    return preg_replace("/[^a-z0-9]+/", "-", strtolower($domain));
}

function logo_paths(string $domain): array {
    $slug = logo_slug($domain);
    $dir = __DIR__ . "/../../logos";
    return [
        "dir" => $dir,
        "png" => $dir . "/" . $slug . ".png",
        "ico" => $dir . "/" . $slug . ".ico",
        "public_png" => "public/logos/" . $slug . ".png",
        "public_ico" => "public/logos/" . $slug . ".ico",
    ];
}

function cache_registrar_logo(string $registrar): ?string {
    $domain = registrar_domain_for_logo($registrar);
    if (!$domain) return null;
    $paths = logo_paths($domain);
    if (is_file($paths["png"])) return $paths["public_png"];
    if (is_file($paths["ico"])) return $paths["public_ico"];
    if (!is_dir($paths["dir"])) {
        @mkdir($paths["dir"], 0775, true);
    }
    $context = stream_context_create([
        "http" => [
            "timeout" => 5,
            "user_agent" => "DomainManager-Logo/1.0",
        ],
    ]);
    $remote_png = "https://logo.clearbit.com/" . $domain;
    $raw = @file_get_contents($remote_png, false, $context);
    if ($raw) {
        @file_put_contents($paths["png"], $raw);
        return $paths["public_png"];
    }
    $remote_ico = "https://icons.duckduckgo.com/ip3/" . $domain . ".ico";
    $raw = @file_get_contents($remote_ico, false, $context);
    if ($raw) {
        @file_put_contents($paths["ico"], $raw);
        return $paths["public_ico"];
    }
    return null;
}
