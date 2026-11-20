<?php

declare(strict_types=1);

namespace App\Web\Lantikan;

use App\Shared\LantikanService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class FormAction
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

        $flow = $this->flow($request->getUri()->getPath());
        $form = $this->lantikanService->formBlueprintForIdentity($legacySession['identityId'], $flow);
        if ($form === null) {
            return $this->redirect('/lantikan/index');
        }

        $result = null;
        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $input = is_array($body) ? $body : [];
            if (isset($input['suggest_email'])) {
                $input['COEmail'] = $this->lantikanService->suggestedEmail((string) ($input['CONm'] ?? ''));
            }

            $result = isset($input['live_submit'])
                ? $this->lantikanService->submitAppointment($legacySession['identityId'], $flow, $input, $this->clientIp($request))
                : $this->lantikanService->dryRunAppointment($flow, $input);
            $form['values'] = $result['values'];

            if (($result['success'] ?? false) === true && isset($result['resultUrl']) && is_string($result['resultUrl'])) {
                return $this->redirect($result['resultUrl']);
            }
        }

        return $this->viewRenderer->render(__DIR__ . '/form', [
            'form' => $form,
            'result' => $result,
        ]);
    }

    private function flow(string $path): string
    {
        return match (true) {
            str_contains($path, 'lantik-phs') => 'phs',
            str_contains($path, 'lantik-khas') => 'khas',
            str_contains($path, 'lantik-spdp') => 'spdp',
            default => 'staf-baru',
        };
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
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
