<?php

declare(strict_types=1);

namespace App\Web\StaffRecordOverview;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\StaffRecordOverviewService;
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
        private StaffRecordOverviewService $overviewService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $path = $request->getUri()->getPath();
        $key = $this->overviewService->keyFromPath($path);
        if ($key === null) {
            return $this->responseFactory->createResponse(404);
        }

        $variant = match ($path) {
            '/pasport-permit/lihatpasport' => 'passport',
            '/pasport-permit/lihatpermitkerja' => 'permit',
            default => '',
        };
        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $detail = $icno === '' ? null : $this->overviewService->detail($key, $id, $icno, $variant);

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', [
                'detail' => $detail,
                'backUrl' => $this->backUrl($key),
            ])
            ->withStatus($detail === null ? 404 : 200);
    }

    private function backUrl(string $key): string
    {
        return match ($key) {
            'akaun' => '/akaun/senarai-akaun',
            'vaksinasi' => '/vaksinasi/index',
            default => '/' . $key . '/view',
        };
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
