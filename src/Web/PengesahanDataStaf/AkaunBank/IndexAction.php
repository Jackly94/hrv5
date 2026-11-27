<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf\AkaunBank;

use App\Shared\AuthorizationService;
use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\PengesahanDataStaf\AkaunBankVerificationService;
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
        private AkaunBankVerificationService $service,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $access = ActionSupport::requireControllerAccess(
            $request,
            $this->responseFactory,
            $this->sessionReader,
            $this->employees,
            $this->authorization,
            'pds-akaun-bank',
        );
        if ($access['response'] instanceof ResponseInterface) {
            return $access['response'];
        }

        return $this->viewRenderer->render(__DIR__ . '/index', [
            'dashboard' => $this->service->dashboard(),
        ]);
    }
}
