<?php

declare(strict_types=1);

namespace App\Web\ExternalLogin;

use App\Shared\ExternalLoginResult;
use App\Shared\ExternalLoginService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ExternalLoginService $loginService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $values = [
            'username' => '',
        ];
        $result = null;

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];

            $values['username'] = is_string($body['username'] ?? null) ? $body['username'] : '';
            $password = is_string($body['password'] ?? null) ? $body['password'] : '';

            try {
                $result = $this->loginService->login(
                    $values['username'],
                    $password,
                    $this->clientIp($request),
                );
            } catch (\Throwable) {
                $result = ExternalLoginResult::failure([
                    'general' => 'External login is unavailable. Please try again later.',
                ]);
            }
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'values' => $values,
            'result' => $result,
        ]);
    }

    private function clientIp(ServerRequestInterface $request): string
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
}
