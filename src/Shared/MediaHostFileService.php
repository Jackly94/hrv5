<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\UploadedFileInterface;

final readonly class MediaHostFileService
{
    private const DEFAULT_BASE_URL = 'https://mediahost.ums.edu.my';
    private const DEFAULT_UPLOADER_ID = '0302';
    private const UPLOAD_PATH = '/api/v1/upload';
    private const DELETE_PATH = '/api/v1/delete-file';
    private const VIEW_PATH = '/api/v1/viewFile/';
    private const DOWNLOAD_PATH = '/api/v1/downloadFile/';
    private const INFO_PATH = '/api/v1/getFileInfo/';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private array $config = []) {}

    /**
     * @return array{success: bool, hash?: string, message?: string}
     */
    public function uploadDocument(
        UploadedFileInterface $file,
        string $directory,
        string $userId,
        string $category = '04',
        string $description = '-',
    ): array {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'File upload service is unavailable on this server.'];
        }

        $filename = $file->getClientFilename();
        if ($filename === null || trim($filename) === '') {
            return ['success' => false, 'message' => 'Nama fail tidak sah.'];
        }

        $credentials = $this->credentials();
        if ($credentials === null) {
            return ['success' => false, 'message' => 'Tetapan MediaHost tidak lengkap.'];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'hronline_upload_');
        if ($tempPath === false) {
            return ['success' => false, 'message' => 'Fail sementara tidak dapat disediakan.'];
        }

        try {
            $file->getStream()->rewind();
            file_put_contents($tempPath, $file->getStream()->getContents());

            $curl = curl_init($this->endpoint(self::UPLOAD_PATH));
            if ($curl === false) {
                return ['success' => false, 'message' => 'File upload service is unavailable.'];
            }

            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $this->intConfig('connectTimeout', 5),
                CURLOPT_TIMEOUT => $this->intConfig('timeout', 60),
                CURLOPT_POSTFIELDS => [
                    'secret_key' => $credentials['secretKey'],
                    'public_key' => $credentials['publicKey'],
                    'uploader_id' => $credentials['uploaderId'],
                    'user_id' => $userId,
                    'autocreatedirectory' => '1',
                    'directory' => $directory,
                    'file_title' => $filename,
                    'file' => new \CURLFile($tempPath, $file->getClientMediaType() ?: 'application/pdf', $filename),
                    'file_description' => $description,
                    'category' => $category,
                    'tag' => '-',
                    'accessibility' => '01',
                    'overwrite' => '0',
                    'hashcode' => '',
                ],
            ]);

            $responseBody = curl_exec($curl);
            $error = curl_error($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

            if ($responseBody === false || $error !== '') {
                return ['success' => false, 'message' => $error !== '' ? $error : 'MediaHost tidak memberi respons.'];
            }

            $payload = json_decode((string) $responseBody, true);
            if (!is_array($payload)) {
                return ['success' => false, 'message' => 'Respons MediaHost tidak sah.'];
            }

            if ($statusCode >= 400 || ($payload['status'] ?? false) !== true) {
                return [
                    'success' => false,
                    'message' => is_scalar($payload['message'] ?? null) ? (string) $payload['message'] : 'Muat naik dokumen gagal.',
                ];
            }

            $hash = $payload['file_name_hashcode'] ?? null;
            if (!is_string($hash) || trim($hash) === '') {
                return ['success' => false, 'message' => 'MediaHost tidak memulangkan kod fail.'];
            }

            return ['success' => true, 'hash' => $hash];
        } finally {
            if (is_file($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function deleteDocument(string $hashcode, string $userId): array
    {
        if (trim($hashcode) === '' || $hashcode === 'deleted') {
            return ['success' => true];
        }

        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'File delete service is unavailable on this server.'];
        }

        $credentials = $this->credentials();
        if ($credentials === null) {
            return ['success' => false, 'message' => 'Tetapan MediaHost tidak lengkap.'];
        }

        $curl = curl_init($this->endpoint(self::DELETE_PATH));
        if ($curl === false) {
            return ['success' => false, 'message' => 'File delete service is unavailable.'];
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->intConfig('connectTimeout', 5),
            CURLOPT_TIMEOUT => $this->intConfig('timeout', 60),
            CURLOPT_POSTFIELDS => [
                'secret_key' => $credentials['secretKey'],
                'public_key' => $credentials['publicKey'],
                'uploader_id' => $credentials['uploaderId'],
                'user_id' => $userId,
                'hashcode' => $hashcode,
            ],
        ]);

        $responseBody = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        if ($responseBody === false || $error !== '') {
            return ['success' => false, 'message' => $error !== '' ? $error : 'MediaHost tidak memberi respons.'];
        }

        $payload = json_decode((string) $responseBody, true);
        if (!is_array($payload)) {
            return ['success' => false, 'message' => 'Respons MediaHost tidak sah.'];
        }

        if ($statusCode >= 400 || ($payload['status'] ?? false) !== true) {
            return [
                'success' => false,
                'message' => is_scalar($payload['message'] ?? null) ? (string) $payload['message'] : 'Padam dokumen gagal.',
            ];
        }

        return ['success' => true];
    }

    /**
     * @return array{success: bool, hash?: string, message?: string}
     */
    public function uploadLocalFile(
        string $path,
        string $filename,
        string $directory,
        string $userId,
        string $category = '04',
        string $description = '-',
    ): array {
        if (!is_file($path) || !is_readable($path)) {
            return ['success' => false, 'message' => 'Fail tidak dijumpai atau tidak boleh dibaca.'];
        }

        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'File upload service is unavailable on this server.'];
        }

        $credentials = $this->credentials();
        if ($credentials === null) {
            return ['success' => false, 'message' => 'Tetapan MediaHost tidak lengkap.'];
        }

        $mimeType = function_exists('mime_content_type') ? (string) @mime_content_type($path) : '';
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        $curl = curl_init($this->endpoint(self::UPLOAD_PATH));
        if ($curl === false) {
            return ['success' => false, 'message' => 'File upload service is unavailable.'];
        }

        try {
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $this->intConfig('connectTimeout', 5),
                CURLOPT_TIMEOUT => $this->intConfig('timeout', 60),
                CURLOPT_POSTFIELDS => [
                    'secret_key' => $credentials['secretKey'],
                    'public_key' => $credentials['publicKey'],
                    'uploader_id' => $credentials['uploaderId'],
                    'user_id' => $userId,
                    'autocreatedirectory' => '1',
                    'directory' => $directory,
                    'file_title' => $filename,
                    'file' => new \CURLFile($path, $mimeType, $filename),
                    'file_description' => $description,
                    'category' => $category,
                    'tag' => '-',
                    'accessibility' => '01',
                    'overwrite' => '0',
                    'hashcode' => '',
                ],
            ]);

            $responseBody = curl_exec($curl);
            $error = curl_error($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

            if ($responseBody === false || $error !== '') {
                return ['success' => false, 'message' => $error !== '' ? $error : 'MediaHost tidak memberi respons.'];
            }

            $payload = json_decode((string) $responseBody, true);
            if (!is_array($payload)) {
                return ['success' => false, 'message' => 'Respons MediaHost tidak sah.'];
            }

            if ($statusCode >= 400 || ($payload['status'] ?? false) !== true) {
                return [
                    'success' => false,
                    'message' => is_scalar($payload['message'] ?? null) ? (string) $payload['message'] : 'Muat naik dokumen gagal.',
                ];
            }

            $hash = $payload['file_name_hashcode'] ?? null;
            if (!is_string($hash) || trim($hash) === '') {
                return ['success' => false, 'message' => 'MediaHost tidak memulangkan kod fail.'];
            }

            return ['success' => true, 'hash' => $hash];
        } finally {
            curl_close($curl);
        }
    }

    public function viewUrl(string $hashcode): string
    {
        $hashcode = trim($hashcode);

        return $hashcode === '' || $hashcode === 'deleted' ? '' : $this->endpoint(self::VIEW_PATH) . rawurlencode($hashcode);
    }

    public function downloadUrl(string $hashcode): string
    {
        $hashcode = trim($hashcode);

        return $hashcode === '' || $hashcode === 'deleted' ? '' : $this->endpoint(self::DOWNLOAD_PATH) . rawurlencode($hashcode);
    }

    /**
     * @return array<string, mixed>
     */
    public function fileInfo(string $hashcode): array
    {
        $hashcode = trim($hashcode);
        if ($hashcode === '' || $hashcode === 'deleted') {
            return ['exists' => false, 'message' => 'Dokumen adalah kosong.'];
        }

        if (!function_exists('curl_init')) {
            return ['exists' => false, 'message' => 'File info service is unavailable on this server.'];
        }

        $curl = curl_init($this->endpoint(self::INFO_PATH) . rawurlencode($hashcode));
        if ($curl === false) {
            return ['exists' => false, 'message' => 'File info service is unavailable.'];
        }

        curl_setopt_array($curl, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->intConfig('connectTimeout', 5),
            CURLOPT_TIMEOUT => 20,
        ]);

        $responseBody = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        if ($responseBody === false || $error !== '') {
            return ['exists' => false, 'message' => $error !== '' ? $error : 'MediaHost tidak memberi respons.'];
        }

        $payload = json_decode((string) $responseBody, true);
        if (!is_array($payload) || $statusCode >= 400 || array_key_exists('status', $payload)) {
            return ['exists' => false, 'message' => 'Fail / Hashcode Tidak Wujud.'];
        }

        return ['exists' => true, ...$payload];
    }

    public function fileName(string $hashcode): string
    {
        $info = $this->fileInfo($hashcode);
        $name = $info['file_name'] ?? null;

        return is_scalar($name) && trim((string) $name) !== '' ? (string) $name : '-';
    }

    private function endpoint(string $path): string
    {
        return $this->baseUrl() . $path;
    }

    private function baseUrl(): string
    {
        return rtrim($this->stringConfig('baseUrl', self::DEFAULT_BASE_URL), '/');
    }

    /**
     * @return array{secretKey: string, publicKey: string, uploaderId: string}|null
     */
    private function credentials(): ?array
    {
        $secretKey = $this->stringConfig('secretKey');
        $publicKey = $this->stringConfig('publicKey');
        if ($secretKey === '' || $publicKey === '') {
            return null;
        }

        return [
            'secretKey' => $secretKey,
            'publicKey' => $publicKey,
            'uploaderId' => $this->stringConfig('uploaderId', self::DEFAULT_UPLOADER_ID),
        ];
    }

    private function stringConfig(string $key, string $fallback = ''): string
    {
        $value = $this->config[$key] ?? null;
        if (!is_scalar($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }

    private function intConfig(string $key, int $fallback): int
    {
        $value = $this->config[$key] ?? null;
        if (!is_scalar($value) || !is_numeric($value)) {
            return $fallback;
        }

        $value = (int) $value;

        return $value > 0 ? $value : $fallback;
    }
}
