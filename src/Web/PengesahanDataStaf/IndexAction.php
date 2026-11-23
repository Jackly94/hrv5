<?php

declare(strict_types=1);

namespace App\Web\PengesahanDataStaf;

use App\Shared\LegacySessionReader;
use App\Shared\PengesahanDataStaf\PengesahanDataStafService;
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
        private PengesahanDataStafService $service,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->sessionReader->read($request) === null) {
            return ActionSupport::redirect($this->responseFactory, '/login');
        }

        return $this->viewRenderer->render(__DIR__ . '/index', [
            'sections' => $this->service->sections(),
        ]);
    }
}
