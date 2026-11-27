<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf\Keluarga;

use App\Shared\AuthorizationService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\PengesahanDataStaf\KeluargaVerificationService;
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
        private KeluargaVerificationService $service,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $access = ActionSupport::requireControllerAccess(
            $request,
            $this->responseFactory,
            $this->sessionReader,
            $this->employees,
            $this->authorization,
            'pds-keluarga',
        );
        if ($access['response'] instanceof ResponseInterface) {
            return $access['response'];
        }

        $body = ActionSupport::body($request);
        $selection = is_array($body['selection'] ?? null) ? $body['selection'] : [];
        $remarks = is_scalar($body['remarks'] ?? null) ? trim((string) $body['remarks']) : '';
        $action = isset($body['accept']) ? 'accept' : (isset($body['reject']) ? 'reject' : '');
        $result = $this->service->verify('oku', $selection, $action, $remarks);

        return ActionSupport::redirect(
            $this->responseFactory,
            '/pengesahan-data-staf/keluarga?' . ($result['success'] ? 'updated=1' : 'error=1'),
        );
    }
}
