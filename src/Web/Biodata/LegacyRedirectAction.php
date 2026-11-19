<?php

declare(strict_types=1);

namespace App\Web\Biodata;

use App\Shared\LegacyUrl;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LegacyRedirectAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        $location = LegacyUrl::to($uri->getPath());
        if ($uri->getQuery() !== '') {
            $location .= '?' . $uri->getQuery();
        }

        $status = strtoupper($request->getMethod()) === 'GET' ? 302 : 307;

        return $this->responseFactory
            ->createResponse($status)
            ->withHeader('Location', $location);
    }
}
