<?php

declare(strict_types=1);

namespace App\Web\Login;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    private const PORTAL_URL = 'https://kinabalu.ums.edu.my/menu';

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $loginMethod = $request->getCookieParams()['hr_login_method'] ?? null;

        if ($loginMethod === 'portal') {
            return $this->redirect(self::PORTAL_URL);
        }

        if ($loginMethod === 'external') {
            return $this->redirect('/external-login');
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'portalUrl' => self::PORTAL_URL,
        ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
