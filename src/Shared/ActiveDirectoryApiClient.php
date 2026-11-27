<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class ActiveDirectoryApiClient
{
    private const BASE_URL = 'https://api.ums.edu.my';

    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    public function login(string $username, string $password): array
    {
        if (!$this->enabled()) {
            return $this->disabledResult();
        }

        return $this->post('/api/v2/basic/ad/login', [
            'txtUserId' => $username,
            'txtUserPassword' => $password,
        ]);
    }

    /**
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    public function find(string $userid, string $type = 'EMAIL'): array
    {
        if (!$this->enabled()) {
            return $this->disabledResult();
        }

        return $this->post('/api/v1/ad/find', [
            'userid' => $userid,
            'type' => strtoupper($type),
        ]);
    }

    /**
     * @param array<string, string|bool> $staff
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    public function createUser(array $staff): array
    {
        if (!$this->enabled()) {
            return $this->disabledResult();
        }

        return $this->post('/api/v1/ad/createUser', $staff);
    }

    /**
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    public function createUserForIcno(string $icno): array
    {
        if (!$this->enabled()) {
            return $this->disabledResult();
        }

        $payload = $this->staffPayload($icno, true);
        if ($payload === null) {
            return $this->failure('Staff biodata not found for AD create.');
        }

        return $this->createUser($payload);
    }

    /**
     * @param array<string, string|bool> $staff
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    public function updateUser(string $userId, array $staff): array
    {
        if (!$this->enabled()) {
            return $this->disabledResult();
        }

        return $this->post('/api/v1/ad/updateUser/' . rawurlencode($userId), $staff);
    }

    /**
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    public function updateUserForIcno(string $userId, string $icno): array
    {
        if (!$this->enabled()) {
            return $this->disabledResult();
        }

        $payload = $this->staffPayload($icno, false);
        if ($payload === null) {
            return $this->failure('Staff biodata not found for AD update.');
        }

        return $this->updateUser($userId, $payload);
    }

    public function enabled(): bool
    {
        return $this->credential('HRONLINE_AD_ENABLE') === '1'
            && $this->credential('HRONLINE_AD_PUBLIC_KEY') !== ''
            && $this->credential('HRONLINE_AD_SECRET_KEY') !== '';
    }

    /**
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    private function post(string $path, array $fields): array
    {
        $fields['public_key'] = $this->credential('HRONLINE_AD_PUBLIC_KEY');
        $fields['secret_key'] = $this->credential('HRONLINE_AD_SECRET_KEY');

        $boundary = '----hronline-yii3-' . bin2hex(random_bytes(8));
        $body = $this->multipartBody($fields, $boundary);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: multipart/form-data; boundary={$boundary}\r\n",
                'content' => $body,
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($this->baseUrl() . $path, false, $context);
        $headers = $http_response_header ?? [];
        $httpStatus = $this->httpStatus($headers);
        if ($response === false) {
            return [
                'enabled' => true,
                'success' => false,
                'message' => 'Active Directory API request failed without a response.',
                'data' => null,
            ];
        }

        $decoded = json_decode($response, true);
        $data = is_array($decoded) ? $decoded : ['raw' => $response];
        $success = ($httpStatus === 0 || ($httpStatus >= 200 && $httpStatus < 300)) && $this->success($data);

        return [
            'enabled' => true,
            'success' => $success,
            'message' => $this->message($data, $success, $httpStatus),
            'data' => $data + ['httpStatus' => $httpStatus],
        ];
    }

    /**
     * @return array<string, string|bool>|null
     */
    private function staffPayload(string $icno, bool $includeAdUserId): ?array
    {
        $row = $this->db->createCommand(
            <<<'SQL'
            SELECT bio.ICNO, bio.COEmail, bio.CONm, bio.COOldID, bio.COOffTelNo, bio.COHPhoneNo,
                   dept.shortname AS dept_code, dept.fullname AS department_name,
                   title.Title AS title_name, campus.campus_name
            FROM hronline.tblprcobiodata bio
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hronline.title title ON title.TitleCd = bio.TitleCd
            LEFT JOIN hronline.campus campus ON campus.campus_id = bio.campus_id
            WHERE bio.ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        if (!is_array($row)) {
            return null;
        }

        $payload = [
            'ic_no' => $this->stringValue($row['ICNO'] ?? null),
            'email' => $this->stringValue($row['COEmail'] ?? null),
            'name' => $this->stringValue($row['CONm'] ?? null),
            'dept_code' => $this->stringValue($row['dept_code'] ?? null),
            'department' => $this->stringValue($row['department_name'] ?? null),
            'staff_no' => $this->stringValue($row['COOldID'] ?? null),
            'office_phone_no' => $this->stringValue($row['COOffTelNo'] ?? null),
            'handphone_no' => $this->stringValue($row['COHPhoneNo'] ?? null),
            'title' => $this->stringValue($row['title_name'] ?? null),
            'city' => $this->stringValue($row['campus_name'] ?? null),
            'is_active' => true,
        ];

        if ($includeAdUserId) {
            $payload['ad_userid'] = $this->emailLocalPart($payload['email']);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function multipartBody(array $fields, string $boundary): string
    {
        $body = '';
        foreach ($fields as $name => $value) {
            $body .= "--{$boundary}\r\n";
            $body .= 'Content-Disposition: form-data; name="' . addcslashes((string) $name, '"\\') . "\"\r\n\r\n";
            $body .= (is_bool($value) ? ($value ? '1' : '0') : (string) $value) . "\r\n";
        }

        return $body . "--{$boundary}--\r\n";
    }

    /**
     * @param array<string, mixed> $data
     */
    private function success(array $data): bool
    {
        $status = $data['status'] ?? null;
        if ($status === true || $status === 'true' || $status === 'Successful login') {
            return true;
        }

        if ($status === false || $status === 'false') {
            return false;
        }

        if (array_key_exists('errors', $data)) {
            $errors = $data['errors'];

            if (is_array($errors)) {
                return $errors === [];
            }

            return $errors === null || $errors === '';
        }

        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function message(array $data, bool $success, int $httpStatus): string
    {
        foreach (['message', 'status'] as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                return (string) $data[$key];
            }
        }

        if (!$success && $httpStatus > 0) {
            return 'Active Directory request failed with HTTP ' . $httpStatus . '.';
        }

        return $success ? 'Active Directory request completed.' : 'Active Directory request failed.';
    }

    private function httpStatus(array $headers): int
    {
        $statusLine = is_string($headers[0] ?? null) ? $headers[0] : '';
        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function baseUrl(): string
    {
        $url = $this->credential('HRONLINE_AD_BASE_URL');

        return $url !== '' ? rtrim($url, '/') : self::BASE_URL;
    }

    private function credential(string $name): string
    {
        $value = getenv($name);

        return is_string($value) ? trim($value) : '';
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function emailLocalPart(string $email): string
    {
        $position = strpos($email, '@');

        return $position === false ? $email : substr($email, 0, $position);
    }

    /**
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    private function disabledResult(): array
    {
        return [
            'enabled' => false,
            'success' => false,
            'message' => 'Active Directory API is disabled. Set HRONLINE_AD_ENABLE=1 and credentials to enable.',
            'data' => null,
        ];
    }

    /**
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    private function failure(string $message): array
    {
        return [
            'enabled' => true,
            'success' => false,
            'message' => $message,
            'data' => null,
        ];
    }
}
