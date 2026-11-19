<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\ServerRequestInterface;

final class LegacySessionReader
{
    private const YII2_SESSION_NAME = 'hrv4';

    /**
     * @return array{identityId: string, authKey: string, expiresAt: int}|null
     */
    public function read(ServerRequestInterface $request): ?array
    {
        $sessionId = $request->getCookieParams()[self::YII2_SESSION_NAME] ?? null;
        if (!is_string($sessionId) || $sessionId === '') {
            return null;
        }

        $previousSessionName = session_name();
        $previousSessionId = session_id();
        $previousCacheLimiter = session_cache_limiter();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_name(self::YII2_SESSION_NAME);
        session_id($sessionId);
        session_cache_limiter('');
        session_start(['read_and_close' => true]);

        $identityId = $_SESSION['__id'] ?? null;
        $authKey = $_SESSION['__authKey'] ?? null;
        $expiresAt = $_SESSION['__expire'] ?? null;

        session_id($previousSessionId);
        session_name($previousSessionName);
        session_cache_limiter($previousCacheLimiter);

        if (!is_string($identityId) || !is_string($authKey) || !is_int($expiresAt)) {
            return null;
        }

        if ($expiresAt < time()) {
            return null;
        }

        return [
            'identityId' => $identityId,
            'authKey' => $authKey,
            'expiresAt' => $expiresAt,
        ];
    }
}
