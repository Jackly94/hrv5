<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\PasportPermit;

use App\Shared\Biodata\Sections\PasportPermitCrudService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Web\Biodata\Sections\StaffRecordSectionActionSupport as Support;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private PasportPermitCrudService $pasportPermitCrudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return Support::redirect($this->responseFactory, '/login');
        }

        $recordKey = $this->pasportPermitCrudService->keyFromPath($request->getUri()->getPath());
        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $targetIcno = Support::cleanQueryValue((string) ($query['icno'] ?? ''));
        $icno = $targetIcno !== ''
            ? $targetIcno
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        if ($recordKey !== null && $icno !== '' && $id !== '') {
            $this->pasportPermitCrudService->delete($recordKey, $icno, $id, Support::clientIp($request));
        }

        $backUrl = $targetIcno !== ''
            ? '/biodata/pasport-permit/adminview?icno=' . rawurlencode($targetIcno)
            : '/biodata/pasport-permit/view';

        return Support::redirect($this->responseFactory, Support::appendQuery($backUrl, ['deleted' => '1']));
    }
}
