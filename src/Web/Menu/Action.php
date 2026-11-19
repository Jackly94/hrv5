<?php

declare(strict_types=1);

namespace App\Web\Menu;

use App\Shared\LegacySessionReader;
use App\Shared\MenuService;
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
        private MenuService $menuService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $menu = $this->menuService->menuForIdentity($legacySession['identityId']);
        $query = $request->getQueryParams();
        $showDebug = (string) ($query['debug'] ?? '') === '1';

        if ($menu === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/template', ['menu' => null, 'showDebug' => $showDebug])
                ->withStatus(403);
        }

        return $this->viewRenderer->render(__DIR__ . '/template', ['menu' => $menu, 'showDebug' => $showDebug]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
