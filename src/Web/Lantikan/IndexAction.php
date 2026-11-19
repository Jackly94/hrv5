<?php

declare(strict_types=1);

namespace App\Web\Lantikan;

use App\Shared\LantikanService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private LantikanService $lantikanService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $dashboard = $this->lantikanService->dashboardForIdentity($legacySession['identityId']);
        if ($dashboard === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/index', ['dashboard' => null])
                ->withStatus(403);
        }

        return $this->viewRenderer->render(__DIR__ . '/index', ['dashboard' => $dashboard]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
