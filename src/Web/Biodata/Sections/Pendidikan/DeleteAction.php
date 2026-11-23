<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Pendidikan;

use App\Shared\Biodata\Sections\PendidikanCrudService;
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
        private PendidikanCrudService $pendidikanCrudService,
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
        if ($icno !== '' && $id !== '') {
            $this->pendidikanCrudService->delete($icno, $id, Support::clientIp($request));
        }

        $backUrl = $targetIcno !== ''
            ? '/biodata/pendidikan/adminview?icno=' . rawurlencode($targetIcno)
            : '/biodata/pendidikan/view';

        return Support::redirect($this->responseFactory, Support::appendQuery($backUrl, ['deleted' => '1']));
    }
}
