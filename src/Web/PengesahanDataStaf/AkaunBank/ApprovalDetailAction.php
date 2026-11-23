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

final readonly class ApprovalDetailAction
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

        $detail = $this->service->detail(ActionSupport::id($request), 'approval', false);

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', ['detail' => $detail, 'errors' => [], 'remarks' => ''])
            ->withStatus($detail === null ? 404 : 200);
    }
}
