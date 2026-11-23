<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\SejarahPerubatan;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\Biodata\Sections\SejarahPerubatanCrudService;
use App\Web\Biodata\Sections\StaffRecordSectionActionSupport as Support;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteAction
{
    private const KEY = 'sejarahperubatan';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private SejarahPerubatanCrudService $sejarahPerubatanCrudService,
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
        if ($id !== '' && $icno !== '') {
            $this->sejarahPerubatanCrudService->delete($icno, $id, Support::clientIp($request));
        }

        $backUrl = $targetIcno !== ''
            ? '/biodata/' . self::KEY . '/adminview?icno=' . rawurlencode($targetIcno)
            : $this->sejarahPerubatanCrudService->backUrl();

        return Support::redirect($this->responseFactory, Support::appendQuery($backUrl, ['deleted' => '1']));
    }
}
