<?php

declare(strict_types=1);

namespace App\Web\Lantikan;

use App\Shared\LantikanService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LegacyRedirectAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private LantikanService $lantikanService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->sessionReader->read($request) === null) {
            return $this->redirect('/login');
        }

        $path = ltrim($request->getUri()->getPath(), '/');
        $query = $request->getUri()->getQuery();

        return $this->redirect($this->lantikanService->legacyUrl($path) . ($query === '' ? '' : '?' . $query));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
