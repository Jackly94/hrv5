<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final readonly class StaffRecordSectionActionSupport
{
    /**
     * @param array<string, string> $query
     */
    public static function appendQuery(string $url, array $query): string
    {
        $queryString = http_build_query($query);
        if ($url === '' || $queryString === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $queryString;
    }

    public static function cleanQueryValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return trim(strtok($value, '?&') ?: $value);
    }

    /**
     * @return array<string, UploadedFileInterface|null>
     */
    public static function uploadedFiles(ServerRequestInterface $request): array
    {
        $files = [];
        foreach ($request->getUploadedFiles() as $name => $file) {
            $files[$name] = $file instanceof UploadedFileInterface ? $file : null;
        }

        return $files;
    }

    public static function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        foreach (['HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $value = $server[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim(explode(',', $value)[0]);
            }
        }

        return 'UNKNOWN';
    }

    public static function redirect(ResponseFactoryInterface $responseFactory, string $url): ResponseInterface
    {
        return $responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
