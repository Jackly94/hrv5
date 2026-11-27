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

final readonly class ApprovalAction
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
            'acc-bank-approval',
        );
        if ($access['response'] instanceof ResponseInterface) {
            return $access['response'];
        }

        return $this->viewRenderer->render(__DIR__ . '/queue', [
            'page' => $this->service->approvalPage(
                ActionSupport::search($request),
                ActionSupport::page($request, 'pending_page'),
                ActionSupport::page($request, 'history_page'),
            ),
        ]);
    }
}
