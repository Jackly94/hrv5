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
            } catch (\Throwable $throwable) {
                error_log((string) $throwable);

                $result = ExternalLoginResult::failure([
                    'general' => 'External login is unavailable. Please try again later.',
                ]);
            }
        }

        $response = $this->viewRenderer->render(__DIR__ . '/template', [
            'values' => $values,
            'result' => $result,
        ]);

        if ($result?->success === true && $result->legacySessionId !== null) {
            return $response->withAddedHeader(
                'Set-Cookie',
                $this->legacySessionCookieHeader($result->legacySessionId, $request),
            )->withAddedHeader(
                'Set-Cookie',
                $this->rememberLoginMethodCookieHeader($request),
            );
        }

        return $response;
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

    private function legacySessionCookieHeader(string $sessionId, ServerRequestInterface $request): string
    {
        $cookie = 'hrv4=' . rawurlencode($sessionId) . '; Path=/; HttpOnly; SameSite=Lax';

        if ($request->getUri()->getScheme() === 'https') {
            $cookie .= '; Secure';
        }

        return $cookie;
    }

    private function rememberLoginMethodCookieHeader(ServerRequestInterface $request): string
    {
        $cookie = 'hr_login_method=external; Max-Age=1209600; Path=/; HttpOnly; SameSite=Lax';

        if ($request->getUri()->getScheme() === 'https') {
            $cookie .= '; Secure';
        }

        return $cookie;
    }
}
