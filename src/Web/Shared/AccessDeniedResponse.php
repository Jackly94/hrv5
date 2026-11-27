<?php

declare(strict_types=1);

namespace App\Web\Shared;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class AccessDeniedResponse
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

    public function render(
        string $title = 'Akses Tidak Dibenarkan',
        string $message = 'Anda tidak mempunyai akses untuk paparan ini.',
        string $backUrl = '/menu',
    ): ResponseInterface {
        return $this->viewRenderer
            ->render(__DIR__ . '/access-denied', [
                'page' => [
                    'title' => $title,
                    'message' => $message,
                    'backUrl' => $backUrl,
                ],
            ])
            ->withStatus(403);
    }
}
