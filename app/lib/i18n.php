<?php

function i18n_load(string $lang): array {
    $lang = strtolower($lang);
    $file = __DIR__ . "/../lang/" . $lang . ".php";
    if (!is_file($file)) {
        $file = __DIR__ . "/../lang/en.php";
    }
    return require $file;
}

function t(string $key, array $vars = []): string {
    $translations = $GLOBALS["i18n"] ?? [];
    $text = $translations[$key] ?? $key;
    foreach ($vars as $k => $v) {
        $text = str_replace("{" . $k . "}", (string)$v, $text);
    }
    return $text;
}
