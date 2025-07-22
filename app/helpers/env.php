<?php

function env(string $key, $default = null)
{
    static $vars = null;


    if ($vars === null) {
        $envPath = __DIR__ . '/../.env';
        if (!file_exists($envPath)) return $default;
        $vars = parse_ini_file($envPath, false, INI_SCANNER_TYPED);
    }


    return $vars[$key] ?? $default;
}
