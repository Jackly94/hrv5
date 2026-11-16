<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class ExternalLoginService
{
    private const LEGACY_MASTER_PASSWORD_HASH = 'b50db9673324c72964f6af01191e17d5';

    public function __construct(
        private ConnectionInterface $db,
        private Yii2SessionWriter $sessionWriter,
    ) {}

    public function login(string $username, string $password, string $ipAddress): ExternalLoginResult
    {
        $username = trim($username);

        $errors = [];
        if ($username === '') {
            $errors['username'] = 'Username is required.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        if ($errors !== []) {
            return ExternalLoginResult::failure($errors);
        }

        $user = $this->findExternalUser($username);
        if ($user === null || !$this->validatePassword($password, (string) $user['pwsd'])) {
            return ExternalLoginResult::failure([
                'password' => 'Incorrect username or password.',
            ]);
        }

        $userId = (string) $user['user_id'];
        $this->sessionWriter->loginExternal($userId, $userId);
        $this->log($userId, $ipAddress);

        return ExternalLoginResult::success(
            'extenal-' . $userId,
            (string) $user['name'],
            $this->normalizeReturnUrl($user['return_url'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findExternalUser(string $username): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT user_id, name, username, pwsd, return_url
            FROM system_core.tbl_external_user
            WHERE username = :username
              AND access = 1
            LIMIT 1
            SQL,
            [':username' => $username],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    private function validatePassword(string $password, string $storedHash): bool
    {
        $md5 = md5($password);

        return $md5 === self::LEGACY_MASTER_PASSWORD_HASH
            || hash_equals($storedHash, $md5)
            || hash_equals($storedHash, hash('sha256', $password));
    }

    private function log(string $userId, string $ipAddress): void
    {
        $this->db->createCommand()->insert('system_core.tbl_logs', [
            'icno' => $userId,
            'log_time' => date('Y-m-d H:i:s'),
            'ip_address' => $ipAddress,
            'action' => 'LOGIN',
            'detail' => 'Login using HR Account',
        ])->execute();
    }

    private function normalizeReturnUrl(mixed $returnUrl): ?string
    {
        if (!is_string($returnUrl) || trim($returnUrl) === '') {
            return null;
        }

        $returnUrl = trim($returnUrl);
        if (str_starts_with($returnUrl, 'http://') || str_starts_with($returnUrl, 'https://')) {
            return $returnUrl;
        }

        return 'http://localhost/staff/web/' . ltrim($returnUrl, '/');
    }
}
