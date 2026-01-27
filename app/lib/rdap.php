<?php

function rdap_bootstrap(array $config): array {
    $cache_dir = dirname($config["db_path"]);
    $cache_file = $cache_dir . "/rdap_bootstrap.json";
    $max_age = 7 * 24 * 60 * 60;

    if (is_file($cache_file) && (time() - filemtime($cache_file) < $max_age)) {
        $json = json_decode(file_get_contents($cache_file), true);
        if (is_array($json)) {
            return $json;
        }
    }

    $url = "https://data.iana.org/rdap/dns.json";
    $context = stream_context_create([
        "http" => [
            "timeout" => 5,
            "user_agent" => "DomainManager-RDAP/1.0",
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            @file_put_contents($cache_file, json_encode($json, JSON_PRETTY_PRINT));
            return $json;
        }
    }

    return [];
}

function rdap_find_base_url(array $bootstrap, string $tld): ?string {
    $services = $bootstrap["services"] ?? [];
    foreach ($services as $service) {
        $tlds = $service[0] ?? [];
        $urls = $service[1] ?? [];
        if (in_array($tld, $tlds, true) && isset($urls[0])) {
            return $urls[0];
        }
    }
    return null;
}

function rdap_extract_registrar(array $rdap): ?string {
    $entities = $rdap["entities"] ?? [];
    foreach ($entities as $entity) {
        $roles = $entity["roles"] ?? [];
        if (!in_array("registrar", $roles, true)) {
            continue;
        }
        $vcard = $entity["vcardArray"][1] ?? [];
        foreach ($vcard as $item) {
            $name = $item[0] ?? "";
            if ($name === "fn" || $name === "org") {
                return $item[3] ?? null;
            }
        }
    }
    return null;
}

function rdap_extract_expiration(array $rdap): ?string {
    $events = $rdap["events"] ?? [];
    foreach ($events as $event) {
        $action = strtolower($event["eventAction"] ?? "");
        if ($action === "expiration" || $action === "expires" || $action === "expiry") {
            $date = $event["eventDate"] ?? "";
            try {
                $dt = new DateTime($date);
                return $dt->format("Y-m-d");
            } catch (Exception $e) {
                if (strlen($date) >= 10) {
                    return substr($date, 0, 10);
                }
            }
        }
    }
    return null;
}

function rdap_lookup(array $config, string $domain): array {
    $domain = strtolower(trim($domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = trim($domain);

    if ($domain === "" || strpos($domain, ".") === false) {
        return ["ok" => false, "error" => "Invalid domain."];
    }

    $parts = explode(".", $domain);
    $tld = end($parts);
    $bootstrap = rdap_bootstrap($config);
    $base = rdap_find_base_url($bootstrap, $tld);
    $url = $base ? rtrim($base, "/") . "/domain/" . urlencode($domain) : null;
    $context = stream_context_create([
        "http" => [
            "timeout" => 5,
            "user_agent" => "DomainManager-RDAP/1.0",
        ],
    ]);
    $raw = $url ? @file_get_contents($url, false, $context) : false;
    if (!$raw) {
        $fallback = "https://rdap.org/domain/" . urlencode($domain);
        $raw = @file_get_contents($fallback, false, $context);
        if (!$raw) {
            return ["ok" => false, "error" => "RDAP not available for this TLD."];
        }
        $url = $fallback;
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ["ok" => false, "error" => "Invalid RDAP response."];
    }

    return [
        "ok" => true,
        "registrar" => rdap_extract_registrar($json),
        "expiration" => rdap_extract_expiration($json),
        "rdap_url" => $url,
    ];
}
