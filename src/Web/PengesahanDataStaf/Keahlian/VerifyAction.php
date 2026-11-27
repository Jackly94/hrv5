<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf\Keahlian;

use App\Shared\AuthorizationService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\PengesahanDataStaf\KeahlianVerificationService;
use App\Web\PengesahanDataStaf\ActionSupport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class VerifyAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private AuthorizationService $authorization,
        private KeahlianVerificationService $service,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $body = ActionSupport::body($request);
        $mode = is_scalar($body['mode'] ?? null) ? (string) $body['mode'] : 'staff-groups';
        $access = ActionSupport::requireControllerAccess(
            $request,
            $this->responseFactory,
            $this->sessionReader,
            $this->employees,
            $this->authorization,
            $mode === 'rows' ? 'pds-keahlian-individu' : 'pds-keahlian',
        );
        if ($access['response'] instanceof ResponseInterface) {
            return $access['response'];
        }

        if (strtoupper($request->getMethod()) !== 'POST') {
            return ActionSupport::redirect($this->responseFactory, '/pengesahan-data-staf/keahlian?error=1');
        }

        $selection = is_array($body['selection'] ?? null) ? $body['selection'] : [];

        if ($mode === 'rows') {
            $icno = is_scalar($body['icno'] ?? null) ? trim((string) $body['icno']) : '';
            $result = $this->service->verifyRowsForStaff($icno, $selection, $access['icno']);
            $status = $result['success'] ? 'updated=1' : 'error=1';

            return ActionSupport::redirect(
                $this->responseFactory,
                '/pengesahan-data-staf/keahlian/staf?icno=' . rawurlencode($icno) . '&' . $status,
            );
        }

        $result = $this->service->verifyStaffGroups($selection, $access['icno']);

        return ActionSupport::redirect(
            $this->responseFactory,
            '/pengesahan-data-staf/keahlian?' . ($result['success'] ? 'updated=1' : 'error=1'),
        );
    }
}
