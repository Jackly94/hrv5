<?php

declare(strict_types=1);

namespace App\Web\GambarProfile\Staff;

use App\Shared\EmployeeRepository;
use App\Shared\GambarProfile\ProfilePictureService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class DetailAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private ProfilePictureService $service,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $id = is_scalar($query['id'] ?? null) ? trim((string) $query['id']) : '';
        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);

        return $this->viewRenderer->render(__DIR__ . '/detail', [
            'detail' => $icno === '' ? null : $this->service->detail($id, $icno),
        ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
