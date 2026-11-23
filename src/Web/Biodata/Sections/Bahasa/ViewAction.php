<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Bahasa;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\Biodata\Sections\BahasaService;
use App\Web\Biodata\Sections\StaffRecordSectionActionSupport as Support;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    private const KEY = 'bahasa';

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private BahasaService $bahasaService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return Support::redirect($this->responseFactory, '/login');
        }

        $query = $request->getQueryParams();
        $adminMode = str_contains($request->getUri()->getPath(), 'admin');
        $icno = $adminMode
            ? Support::cleanQueryValue((string) ($query['icno'] ?? $query['id'] ?? ''))
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $section = $icno === '' ? null : $this->bahasaService->listForIcno($icno);
        if ($section !== null) {
            $section['backUrl'] = $adminMode ? '/biodata/adminview?id=' . rawurlencode($icno) : '/biodata/userview';
            $section['actionQuery'] = $adminMode ? ['icno' => $icno] : [];
        }

        $status = '';
        foreach (['created', 'updated', 'deleted'] as $key) {
            if (array_key_exists($key, $query)) {
                $status = $key;
                break;
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/view', [
                'section' => $section,
                'status' => $status,
            ])
            ->withStatus($section === null ? 404 : 200);
    }
}
