<?php

declare(strict_types=1);

namespace App\Shared\GambarProfile;

use App\Shared\MediaHostFileService;

final readonly class BkumsProfilePhotoService
{
    private const BKUMS_PHOTO_BASE = 'https://bkums.ums.edu.my/public/picstf/';
    private const UPLOAD_DIRECTORY = 'Maklumat Rekod Kakitangan/gambar_profile';
    private const UPLOAD_CATEGORY = '01';
    private const RESIZED_WIDTH = 800;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private MediaHostFileService $fileService,
        private array $config = [],
    ) {}

    /**
     * @return array{success: bool, message: string, hash?: string}
     */
    public function backupCurrentPhoto(string $icno): array
    {
        $icno = trim($icno);
        if ($icno === '') {
            return ['success' => false, 'message' => 'No. kad pengenalan staf tidak sah.'];
        }

        $filename = strtoupper(sha1($icno)) . '.jpeg';
        $tempPath = $this->tempPath('bkums_backup_', $filename);
        if ($tempPath === null) {
            return ['success' => false, 'message' => 'Fail sementara tidak dapat disediakan.'];
        }

        try {
            $download = $this->download(self::BKUMS_PHOTO_BASE . rawurlencode($filename), $tempPath);
            if ($download['success'] !== true) {
                return $download;
            }

            $upload = $this->fileService->uploadLocalFile(
                $tempPath,
                $filename,
                self::UPLOAD_DIRECTORY,
                $icno,
                self::UPLOAD_CATEGORY,
            );
            if ($upload['success'] !== true) {
                return ['success' => false, 'message' => $upload['message'] ?? 'Backup gambar semasa tidak berjaya.'];
            }

            return [
                'success' => true,
                'message' => 'Backup gambar semasa berjaya.',
                'hash' => (string) $upload['hash'],
            ];
        } finally {
            $this->deleteIfFile($tempPath);
        }
    }

    public function publishReadinessError(): ?string
    {
        if ($this->boolConfig('dryRun')) {
            return null;
        }

        if ($this->stringConfig('endpoint') === '' || $this->stringConfig('apiKey') === '') {
            return 'Tetapan BKUMS tidak lengkap.';
        }

        if (!function_exists('curl_init')) {
            return 'BKUMS publish service is unavailable on this server.';
        }

        return null;
    }

    /**
     * @return array{success: bool, message: string, payload?: array<string, mixed>}
     */
    public function publishMediaHostPhoto(string $hashcode, string $icno): array
    {
        $hashcode = trim($hashcode);
        $icno = trim($icno);
        if ($hashcode === '' || $hashcode === 'deleted') {
            return ['success' => false, 'message' => 'Kod fail MediaHost tidak sah.'];
        }

        if ($icno === '') {
            return ['success' => false, 'message' => 'No. kad pengenalan staf tidak sah.'];
        }

        if ($this->boolConfig('dryRun')) {
            return [
                'success' => true,
                'message' => 'Dry-run BKUMS publish berjaya.',
                'payload' => ['dryRun' => true, 'hashcode' => $hashcode, 'icno' => $icno],
            ];
        }

        $readinessError = $this->publishReadinessError();
        if ($readinessError !== null) {
            return ['success' => false, 'message' => $readinessError];
        }

        $endpoint = $this->stringConfig('endpoint');
        $apiKey = $this->stringConfig('apiKey');
        $downloadPath = $this->tempPath('bkums_publish_', $hashcode . '.jpeg');
        if ($downloadPath === null) {
            return ['success' => false, 'message' => 'Fail sementara tidak dapat disediakan.'];
        }

        $publishPath = $downloadPath;
        $resizedPath = null;

        try {
            $download = $this->download($this->fileService->downloadUrl($hashcode), $downloadPath);
            if ($download['success'] !== true) {
                return $download;
            }

            $resize = $this->resizeIfNeeded($downloadPath);
            if ($resize['success'] !== true) {
                return ['success' => false, 'message' => $resize['message']];
            }

            $publishPath = (string) ($resize['path'] ?? $downloadPath);
            $resizedPath = $publishPath === $downloadPath ? null : $publishPath;

            return $this->publishFile($endpoint, $apiKey, $publishPath, $icno);
        } finally {
            $this->deleteIfFile($downloadPath);
            if ($resizedPath !== null) {
                $this->deleteIfFile($resizedPath);
            }
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function download(string $url, string $targetPath): array
    {
        if ($url === '') {
            return ['success' => false, 'message' => 'URL fail tidak sah.'];
        }

        $read = @fopen($url, 'rb');
        if ($read === false) {
            return ['success' => false, 'message' => 'Fail sumber tidak dapat dibuka.'];
        }

        $write = @fopen($targetPath, 'wb');
        if ($write === false) {
            fclose($read);

            return ['success' => false, 'message' => 'Fail sementara tidak dapat ditulis.'];
        }

        try {
            stream_copy_to_stream($read, $write);
        } finally {
            fclose($read);
            fclose($write);
        }

        if (!is_file($targetPath) || filesize($targetPath) === 0) {
            return ['success' => false, 'message' => 'Fail sumber kosong atau tidak berjaya dimuat turun.'];
        }

        return ['success' => true, 'message' => 'Fail berjaya dimuat turun.'];
    }

    /**
     * @return array{success: bool, message: string, path?: string}
     */
    private function resizeIfNeeded(string $path): array
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return ['success' => false, 'message' => 'Fail gambar tidak sah atau tidak boleh dibaca.'];
        }

        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        if ($width <= 1000) {
            return ['success' => true, 'message' => 'Resize tidak diperlukan.', 'path' => $path];
        }

        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatetruecolor')) {
            return ['success' => false, 'message' => 'Fungsi resize gambar tidak tersedia pada server.'];
        }

        $source = @imagecreatefromjpeg($path);
        if ($source === false) {
            return ['success' => false, 'message' => 'Fail JPEG tidak boleh diproses.'];
        }

        $resizedHeight = (int) round($height * (self::RESIZED_WIDTH / $width));
        $target = imagecreatetruecolor(self::RESIZED_WIDTH, $resizedHeight);
        if ($target === false) {
            imagedestroy($source);

            return ['success' => false, 'message' => 'Fail resize tidak dapat disediakan.'];
        }

        imagecopyresampled($target, $source, 0, 0, 0, 0, self::RESIZED_WIDTH, $resizedHeight, $width, $height);
        $resizedPath = $this->tempPath('bkums_resized_', basename($path));
        if ($resizedPath === null) {
            imagedestroy($source);
            imagedestroy($target);

            return ['success' => false, 'message' => 'Fail sementara tidak dapat disediakan.'];
        }

        $saved = imagejpeg($target, $resizedPath, 85);
        imagedestroy($source);
        imagedestroy($target);

        if (!$saved) {
            $this->deleteIfFile($resizedPath);

            return ['success' => false, 'message' => 'Fail resize tidak berjaya disimpan.'];
        }

        return ['success' => true, 'message' => 'Fail berjaya diresize.', 'path' => $resizedPath];
    }

    /**
     * @return array{success: bool, message: string, payload?: array<string, mixed>}
     */
    private function publishFile(string $endpoint, string $apiKey, string $path, string $icno): array
    {
        $curl = curl_init($endpoint);
        if ($curl === false) {
            return ['success' => false, 'message' => 'BKUMS publish service is unavailable.'];
        }

        $mimeType = function_exists('mime_content_type') ? (string) @mime_content_type($path) : '';
        if ($mimeType === '') {
            $mimeType = 'image/jpeg';
        }

        try {
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $this->intConfig('connectTimeout', 5),
                CURLOPT_TIMEOUT => $this->intConfig('timeout', 60),
                CURLOPT_HTTPHEADER => ['X-API-KEY: ' . $apiKey],
                CURLOPT_POSTFIELDS => [
                    'icno' => $icno,
                    'photo' => new \CURLFile($path, $mimeType, basename($path)),
                ],
            ]);

            $responseBody = curl_exec($curl);
            $error = curl_error($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

            if ($responseBody === false || $error !== '') {
                return ['success' => false, 'message' => $error !== '' ? $error : 'BKUMS tidak memberi respons.'];
            }

            $payload = json_decode((string) $responseBody, true);
            if (!is_array($payload)) {
                $payload = ['raw' => (string) $responseBody];
            }

            $success = $statusCode < 400 && ($payload['success'] ?? true) !== false && ($payload['status'] ?? true) !== false;

            return [
                'success' => $success,
                'message' => $success ? 'Gambar berjaya diterbitkan ke BKUMS.' : $this->payloadMessage($payload),
                'payload' => $payload,
            ];
        } finally {
            curl_close($curl);
        }
    }

    private function tempPath(string $prefix, string $suffix): ?string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        if ($path === false) {
            return null;
        }

        $target = $path . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $suffix);
        if (!@rename($path, $target)) {
            $this->deleteIfFile($path);

            return null;
        }

        return $target;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function payloadMessage(array $payload): string
    {
        $message = $payload['message'] ?? $payload['error'] ?? null;

        return is_scalar($message) && trim((string) $message) !== ''
            ? (string) $message
            : 'BKUMS menolak penerbitan gambar.';
    }

    private function stringConfig(string $key): string
    {
        $value = $this->config[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function intConfig(string $key, int $default): int
    {
        $value = $this->config[$key] ?? $default;

        return is_numeric($value) ? max(1, (int) $value) : $default;
    }

    private function boolConfig(string $key): bool
    {
        return filter_var($this->config[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function deleteIfFile(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
