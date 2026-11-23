<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Alamat;

use App\Shared\Biodata\Sections\AlamatService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Web\Biodata\Sections\SectionTemplatePath;
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
        private AlamatService $alamatService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $path = $request->getUri()->getPath();
        $adminMode = str_contains($path, 'admin');
        $icno = $adminMode
            ? $this->cleanQueryValue((string) ($query['icno'] ?? $query['id'] ?? ''))
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $addresses = $icno === '' ? null : $this->alamatService->listForIcno($icno);
        $status = '';
        foreach (['created', 'updated', 'deleted'] as $key) {
            if (($query[$key] ?? '') === '1') {
                $status = $key;
                break;
            }
        }

        return $this->viewRenderer
            ->render(SectionTemplatePath::resolve($request, __DIR__, 'view'), [
                'addresses' => $addresses,
                'status' => $status,
                'backUrl' => $adminMode ? '/biodata/adminview?id=' . rawurlencode($icno) : '/biodata/userview',
                'actionQuery' => $adminMode ? ['icno' => $icno] : [],
            ])
            ->withStatus($addresses === null ? 404 : 200);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
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
