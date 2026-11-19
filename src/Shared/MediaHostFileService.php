<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\UploadedFileInterface;

final readonly class MediaHostFileService
{
    private const UPLOAD_URL = 'https://mediahost.ums.edu.my/api/v1/upload';
    private const DELETE_URL = 'https://mediahost.ums.edu.my/api/v1/delete-file';
    private const VIEW_URL = 'https://mediahost.ums.edu.my/api/v1/viewFile/';
    private const DOWNLOAD_URL = 'https://mediahost.ums.edu.my/api/v1/downloadFile/';
    private const INFO_URL = 'https://mediahost.ums.edu.my/api/v1/getFileInfo/';
    private const SECRET_KEY = 'nWuRQcGNm1FCDew3VpQK83DVFsLklE';
    private const PUBLIC_KEY = 'ca2hNOYNhsaLL0OcH1hXsNYfTG6lO2';
    private const UPLOADER_ID = '0302';

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

        $tempPath = tempnam(sys_get_temp_dir(), 'hronline_upload_');
        if ($tempPath === false) {
            return ['success' => false, 'message' => 'Fail sementara tidak dapat disediakan.'];
        }

        try {
            $file->getStream()->rewind();
            file_put_contents($tempPath, $file->getStream()->getContents());

            $curl = curl_init(self::UPLOAD_URL);
            if ($curl === false) {
                return ['success' => false, 'message' => 'File upload service is unavailable.'];
            }

            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POSTFIELDS => [
                    'secret_key' => self::SECRET_KEY,
                    'public_key' => self::PUBLIC_KEY,
                    'uploader_id' => self::UPLOADER_ID,
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

        $curl = curl_init(self::DELETE_URL);
        if ($curl === false) {
            return ['success' => false, 'message' => 'File delete service is unavailable.'];
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POSTFIELDS => [
                'secret_key' => self::SECRET_KEY,
                'public_key' => self::PUBLIC_KEY,
                'uploader_id' => self::UPLOADER_ID,
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

    public function viewUrl(string $hashcode): string
    {
        $hashcode = trim($hashcode);

        return $hashcode === '' || $hashcode === 'deleted' ? '' : self::VIEW_URL . rawurlencode($hashcode);
    }

    public function downloadUrl(string $hashcode): string
    {
        $hashcode = trim($hashcode);

        return $hashcode === '' || $hashcode === 'deleted' ? '' : self::DOWNLOAD_URL . rawurlencode($hashcode);
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

        $curl = curl_init(self::INFO_URL . rawurlencode($hashcode));
        if ($curl === false) {
            return ['exists' => false, 'message' => 'File info service is unavailable.'];
        }

        curl_setopt_array($curl, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
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
}
