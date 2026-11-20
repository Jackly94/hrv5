<?php

declare(strict_types=1);

namespace App\Web\Lantikan;

use App\Shared\LantikanService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ResultAction
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

        $icno = trim((string) ($request->getQueryParams()['icno'] ?? ''));
        $result = $this->lantikanService->stafBaruResultForIdentity($legacySession['identityId'], $icno);
        if ($result === null) {
            return $this->redirect('/lantikan/lantik-staf-baru');
        }

        return $this->viewRenderer->render(__DIR__ . '/result', [
            'result' => $result,
        ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
