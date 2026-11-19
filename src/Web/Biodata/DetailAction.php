<?php

declare(strict_types=1);

namespace App\Web\Biodata;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class DetailAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $path = $request->getUri()->getPath();
        $query = $request->getQueryParams();
        $isOwnDetail = $path === '/biodata/lihatbiodata';
        $icno = $isOwnDetail
            ? $this->profileService->icnoFromIdentity($legacySession['identityId'])
            : (string) ($query['id'] ?? $query['icno'] ?? '');

        $details = $icno === '' ? null : $this->profileService->biodataDetailsForIcno($icno, !$isOwnDetail);

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', [
                'details' => $details,
                'isOwnDetail' => $isOwnDetail,
            ])
            ->withStatus($details === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
