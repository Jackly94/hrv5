<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf\AkaunBank;

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
        private AkaunBankVerificationService $service,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->sessionReader->read($request) === null) {
            return ActionSupport::redirect($this->responseFactory, '/login');
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
