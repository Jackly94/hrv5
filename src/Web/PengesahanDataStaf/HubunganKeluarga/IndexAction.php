<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf\HubunganKeluarga;

use App\Shared\AuthorizationService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\PengesahanDataStaf\KeluargaVerificationService;
use App\Web\PengesahanDataStaf\ActionSupport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
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
            'pds-keluarga-hubungan',
        );
        if ($access['response'] instanceof ResponseInterface) {
            return $access['response'];
        }

        $query = $request->getQueryParams();

        return $this->viewRenderer->render(__DIR__ . '/../family-queue', [
            'page' => $this->service->queuePage(
                'relation',
                $this->stringQuery($query, 'jenis_carian'),
                $this->stringQuery($query, 'carian_data'),
                ActionSupport::page($request, 'page'),
                isset($query['updated']) ? 'updated' : (isset($query['error']) ? 'error' : ''),
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function stringQuery(array $query, string $name): string
    {
        $value = $query[$name] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
