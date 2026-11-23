<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\BidangKepakaran;

use App\Shared\Biodata\Sections\BidangKepakaranService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Web\Biodata\Sections\StaffRecordSectionActionSupport as Support;
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
        private BidangKepakaranService $bidangKepakaranService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return Support::redirect($this->responseFactory, '/login');
        }

        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $targetIcno = Support::cleanQueryValue((string) ($query['icno'] ?? ''));
        $icno = $targetIcno !== ''
            ? $targetIcno
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $detail = $icno === '' ? null : $this->bidangKepakaranService->detail($id, $icno);
        if ($detail !== null && $targetIcno !== '' && ($detail['updateUrl'] ?? '') !== '') {
            $detail['updateUrl'] = Support::appendQuery((string) $detail['updateUrl'], ['icno' => $targetIcno]);
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', [
                'detail' => $detail,
                'backUrl' => $targetIcno !== ''
                    ? '/biodata/bidangkepakaran/adminview?icno=' . rawurlencode($targetIcno)
                    : '/biodata/bidangkepakaran/view',
            ])
            ->withStatus($detail === null ? 404 : 200);
    }
}
