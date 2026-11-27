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
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private AuthorizationService $authorization,
        private KeahlianVerificationService $service,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $access = ActionSupport::requireControllerAccess(
            $request,
            $this->responseFactory,
            $this->sessionReader,
            $this->employees,
            $this->authorization,
            'pds-keahlian',
        );
        if ($access['response'] instanceof ResponseInterface) {
            return $access['response'];
        }

        $query = $request->getQueryParams();
        $searchType = $this->stringQuery($query, 'jenis_carian');
        $search = $this->stringQuery($query, 'carian_data');
        $signal = isset($query['updated']) ? 'updated' : (isset($query['error']) ? 'error' : '');

        return $this->viewRenderer->render(__DIR__ . '/index', [
            'page' => $this->service->queuePage($searchType, $search, ActionSupport::page($request, 'page'), $signal),
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
