<?php

declare(strict_types=1);

namespace App\Web\Dashboard;

use App\Shared\DashboardService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private DashboardService $dashboardService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $dashboard = $this->dashboardService->dashboardForIdentity($legacySession['identityId']);
        if ($dashboard === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/template', [
                    'dashboard' => null,
                    'identityId' => $legacySession['identityId'],
                ])
                ->withStatus(403);
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'dashboard' => $dashboard,
            'identityId' => $legacySession['identityId'],
        ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
