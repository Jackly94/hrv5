<?php

declare(strict_types=1);

namespace App\Shared;

final class Yii2SessionWriter
{
    private const YII2_SESSION_NAME = 'hrv4';

    public function login(string $identityId, string $authKey, int $timeoutSeconds = 1800): string
    {
        return $this->write([
            '__id' => $identityId,
            '__authKey' => $authKey,
            '__expire' => time() + $timeoutSeconds,
        ]);
    }

    /**
     * @param array<string, mixed> $sessionValues
     */
    private function write(array $sessionValues): string
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

        session_start();
        session_regenerate_id(true);

        foreach ($sessionValues as $key => $value) {
            $_SESSION[$key] = $value;
        }

        $legacySessionId = session_id();

        session_write_close();

        session_id($previousSessionId);
        session_name($previousSessionName);
        session_cache_limiter($previousCacheLimiter);

        return $legacySessionId;
    }
}
