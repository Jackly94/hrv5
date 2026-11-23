<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Akaun;

use App\Shared\Biodata\Sections\AkaunService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private AkaunService $akaunService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $adminMode = str_contains($request->getUri()->getPath(), 'admin');
        $icno = $adminMode
            ? $this->cleanQueryValue((string) ($query['icno'] ?? $query['id'] ?? ''))
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $overview = $icno === '' ? null : $this->akaunService->overview($icno, $adminMode);

        if ($overview !== null && $adminMode) {
            $overview['backUrl'] = '/biodata/adminview?id=' . rawurlencode($icno);
            $overview = $this->akaunService->withActionQuery($overview, ['icno' => $icno]);
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/view', [
                'overview' => $overview,
            ])
            ->withStatus($overview === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }

    private function cleanQueryValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return trim(strtok($value, '?&') ?: $value);
    }
}
