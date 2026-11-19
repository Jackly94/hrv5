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

final readonly class ViewAction
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

        $key = $this->overviewService->keyFromPath($request->getUri()->getPath());
        if ($key === null) {
            return $this->responseFactory->createResponse(404);
        }

        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $overview = $icno === '' ? null : $this->overviewService->overview($key, $icno);

        return $this->viewRenderer
            ->render(__DIR__ . '/view', [
                'overview' => $overview,
            ])
            ->withStatus($overview === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
