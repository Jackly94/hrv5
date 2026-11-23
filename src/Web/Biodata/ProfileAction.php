<?php

declare(strict_types=1);

namespace App\Web\Biodata;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

readonly class ProfileAction
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

        $query = $request->getQueryParams();
        $path = $request->getUri()->getPath();
        $currentIcno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $icno = $currentIcno;

        if (!in_array($path, ['/biodata/userview', '/biodata/lihatbiodata'], true)) {
            $icno = (string) ($query['id'] ?? $query['icno'] ?? $currentIcno);
        }

        $mode = $path === '/biodata/adminview' ? 'admin' : 'user';
        $profile = $icno === '' ? null : $this->profileService->profileForIcno($icno, $mode);
        if ($profile === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/template', [
                    'identityId' => $legacySession['identityId'],
                    'profile' => null,
                ])
                ->withStatus(404);
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'identityId' => $legacySession['identityId'],
            'profile' => $profile,
        ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
