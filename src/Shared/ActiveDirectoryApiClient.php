<?php

declare(strict_types=1);

namespace App\Shared;

final readonly class ActiveDirectoryApiClient
{
    private const BASE_URL = 'https://api.ums.edu.my';

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
     * @param array<string, string> $staff
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
     * @param array<string, string> $staff
     * @return array{enabled: bool, success: bool, message: string, data: array<string, mixed>|null}
     */
    public function updateUser(string $userId, array $staff): array
    {
        if (!$this->enabled()) {
            return $this->disabledResult();
        }

        return $this->post('/api/v1/ad/updateUser/' . rawurlencode($userId), $staff);
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

        $response = @file_get_contents(self::BASE_URL . $path, false, $context);
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
        $success = $this->success($data);

        return [
            'enabled' => true,
            'success' => $success,
            'message' => $this->message($data, $success),
            'data' => $data,
        ];
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

        return !isset($data['errors']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function message(array $data, bool $success): string
    {
        foreach (['message', 'status'] as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                return (string) $data[$key];
            }
        }

        return $success ? 'Active Directory request completed.' : 'Active Directory request failed.';
    }

    private function credential(string $name): string
    {
        $value = getenv($name);

        return is_string($value) ? trim($value) : '';
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
}
