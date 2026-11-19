<?php

declare(strict_types=1);

$env = static function (string $key, ?string $default = null): ?string {
    $value = getenv($key, true);
    if ($value !== false) {
        return $value === '' ? $default : $value;
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value === '' ? $default : $value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    return $default;
};

$connectionNames = [
    'db',
    'db2',
    'db3',
    'db4',
    'db5',
    'db6',
    'db7',
    'db8',
    'db9',
    'db10',
    'db11',
    'db12',
    'db13',
    'db14',
    'db15',
    'db16',
    'db17',
    'db18',
    'db19',
];

$configFor = static function (string $name) use ($env): ?array {
    $suffix = $name === 'db' ? '' : strtoupper(substr($name, 2));
    $prefix = 'HRONLINE_DB' . $suffix;

    $dsn = $env($prefix . '_DSN');
    $username = $env($prefix . '_USERNAME');
    $password = $env($prefix . '_PASSWORD');

    if ($dsn !== null && $username !== null && $password !== null) {
        return [
            'dsn' => $dsn,
            'username' => $username,
            'password' => $password,
        ];
    }

    $legacyYii2Config = dirname(__DIR__, 3) . '/staff/config/' . $name . '.php';
    if (is_file($legacyYii2Config)) {
        $legacy = require $legacyYii2Config;

        return is_array($legacy) ? $legacy : null;
    }

    if ($name === 'db') {
        return [
            'dsn' => 'mysql:host=127.0.0.1;dbname=hronline',
            'username' => 'root',
            'password' => '',
        ];
    }

    return null;
};

$connections = [];
foreach ($connectionNames as $name) {
    $config = $configFor($name);
    if ($config !== null) {
        $connections[$name] = $config;
    }
}

return $connections;
