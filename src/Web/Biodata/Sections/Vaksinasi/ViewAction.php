<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Vaksinasi;

use App\Shared\Biodata\Sections\VaksinasiService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Web\Biodata\Sections\StaffRecordSectionActionSupport as Support;
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
        private VaksinasiService $vaksinasiService,
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
        $overview = $icno === '' ? null : $this->vaksinasiService->overview($icno);
        if ($overview !== null && $adminMode) {
            $overview['backUrl'] = '/biodata/adminview?id=' . rawurlencode($icno);
            $overview = $this->vaksinasiService->withActionQuery($overview, ['icno' => $icno]);
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/view', ['overview' => $overview])
            ->withStatus($overview === null ? 404 : 200);
    }
}
