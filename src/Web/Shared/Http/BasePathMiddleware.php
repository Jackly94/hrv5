<?php

declare(strict_types=1);

namespace App\Web\Shared\Http;

use App\Environment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class BasePathMiddleware implements MiddlewareInterface
{
    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $basePath = Environment::appBasePath();
        if ($basePath === '') {
            return $handler->handle($request);
        }

        $request = $this->stripBasePath($request, $basePath);
        $response = $handler->handle($request);

        return $this->prefixResponse($response, $basePath);
    }

    private function stripBasePath(ServerRequestInterface $request, string $basePath): ServerRequestInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($path !== $basePath && !str_starts_with($path, $basePath . '/')) {
            return $request;
        }

        $normalizedPath = substr($path, strlen($basePath));
        $normalizedPath = $normalizedPath === '' ? '/' : $normalizedPath;

        return $request->withUri($uri->withPath($normalizedPath));
    }

    private function prefixResponse(ResponseInterface $response, string $basePath): ResponseInterface
    {
        $response = $this->prefixLocationHeader($response, $basePath);
        $response = $this->prefixCookiePaths($response, $basePath);

        if (!$this->isHtmlResponse($response)) {
            return $response;
        }

        $html = (string) $response->getBody();
        $prefixedHtml = $this->prefixHtmlUrls($html, $basePath);

        if ($prefixedHtml === $html) {
            return $response;
        }

        return $response->withBody($this->streamFactory->createStream($prefixedHtml));
    }

    private function prefixLocationHeader(ResponseInterface $response, string $basePath): ResponseInterface
    {
        $location = $response->getHeaderLine('Location');
        if (!$this->shouldPrefixPath($location, $basePath)) {
            return $response;
        }

        return $response->withHeader('Location', $basePath . $location);
    }

    private function prefixCookiePaths(ResponseInterface $response, string $basePath): ResponseInterface
    {
        $cookies = $response->getHeader('Set-Cookie');
        if ($cookies === []) {
            return $response;
        }

        $prefixedCookies = array_map(
            static fn (string $cookie): string => preg_replace('~;\s*Path=/($|;)~i', '; Path=' . $basePath . '$1', $cookie) ?? $cookie,
            $cookies,
        );

        return $response->withHeader('Set-Cookie', $prefixedCookies);
    }

    private function isHtmlResponse(ResponseInterface $response): bool
    {
        $contentType = strtolower($response->getHeaderLine('Content-Type'));

        return $contentType === '' || str_contains($contentType, 'text/html');
    }

    private function prefixHtmlUrls(string $html, string $basePath): string
    {
        $baseName = ltrim($basePath, '/');

        return preg_replace_callback(
            '~\b(href|src|action)=(["\'])/([^"\']*)\2~i',
            static function (array $matches) use ($basePath, $baseName): string {
                $target = $matches[3];

                if ($target === '' || str_starts_with($target, '/') || $target === $baseName || str_starts_with($target, $baseName . '/')) {
                    return $matches[0];
                }

                return $matches[1] . '=' . $matches[2] . $basePath . '/' . $target . $matches[2];
            },
            $html,
        ) ?? $html;
    }

    private function shouldPrefixPath(string $path, string $basePath): bool
    {
        return str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && $path !== $basePath
            && !str_starts_with($path, $basePath . '/');
    }
}
