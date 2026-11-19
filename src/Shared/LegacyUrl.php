<?php

declare(strict_types=1);

namespace App\Shared;

final class LegacyUrl
{
    public const BASE = 'https://registrar.ums.edu.my/staff/web';

    public static function base(): string
    {
        return self::BASE;
    }

    public static function to(string $route): string
    {
        return self::BASE . '/' . ltrim($route, '/');
    }

    public static function isLegacy(string $url): bool
    {
        return str_starts_with($url, self::BASE);
    }
}
