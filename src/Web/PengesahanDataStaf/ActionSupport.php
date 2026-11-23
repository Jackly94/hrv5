<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ActionSupport
{
    public static function redirect(ResponseFactoryInterface $responseFactory, string $url): ResponseInterface
    {
        return $responseFactory->createResponse(302)->withHeader('Location', $url);
    }

    public static function search(ServerRequestInterface $request): string
    {
        $query = $request->getQueryParams();
        $value = $query['icno'] ?? $query['q'] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    public static function id(ServerRequestInterface $request): string
    {
        $query = $request->getQueryParams();
        $value = $query['id'] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    public static function page(ServerRequestInterface $request, string $name): int
    {
        $query = $request->getQueryParams();
        $value = $query[$name] ?? 1;
        if (!is_scalar($value)) {
            return 1;
        }

        return max(1, (int) $value);
    }

    /**
     * @return array<string, mixed>
     */
    public static function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();

        return is_array($body) ? $body : [];
    }

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
}
