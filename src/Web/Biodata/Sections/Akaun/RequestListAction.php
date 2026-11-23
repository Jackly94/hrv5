<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Akaun;

use App\Shared\Biodata\Sections\AkaunRequestService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class RequestListAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private AkaunRequestService $requestService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $overview = $icno === '' ? null : $this->requestService->overview($icno);

        return $this->viewRenderer
            ->render(__DIR__ . '/request-list', [
                'overview' => $overview,
            ])
            ->withStatus($overview === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
