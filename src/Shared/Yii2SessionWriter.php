<?php

declare(strict_types=1);

namespace App\Shared;

final class Yii2SessionWriter
{
    private const YII2_SESSION_NAME = 'hrv4';
    private const YII2_EXTERNAL_ID_PREFIX = 'extenal-';

    public function loginExternal(string $userId, string $authKey, int $timeoutSeconds = 1800): void
    {
        $this->write([
            '__id' => self::YII2_EXTERNAL_ID_PREFIX . $userId,
            '__authKey' => $authKey,
            '__expire' => time() + $timeoutSeconds,
        ]);
    }

    /**
     * @param array<string, mixed> $sessionValues
     */
    private function write(array $sessionValues): void
    {
        $previousSessionName = session_name();
        $previousSessionId = session_id();
        $previousCacheLimiter = session_cache_limiter();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_name(self::YII2_SESSION_NAME);
        session_id('');
        session_cache_limiter('');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->isSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        session_regenerate_id(true);

        foreach ($sessionValues as $key => $value) {
            $_SESSION[$key] = $value;
        }

        session_write_close();

        session_id($previousSessionId);
        session_name($previousSessionName);
        session_cache_limiter($previousCacheLimiter);
    }

    private function isSecureRequest(): bool
    {
        return ($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    }
}
