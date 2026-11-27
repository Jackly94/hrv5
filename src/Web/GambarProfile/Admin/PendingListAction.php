<?php

declare(strict_types=1);

namespace App\Web\GambarProfile\Admin;

use App\Shared\EmployeeRepository;
use App\Shared\GambarProfile\ProfilePictureAdminAccessService;
use App\Shared\GambarProfile\ProfilePictureService;
use App\Shared\LegacySessionReader;
use App\Web\Shared\AccessDeniedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class PendingListAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private ProfilePictureAdminAccessService $access,
        private ProfilePictureService $service,
        private AccessDeniedResponse $accessDenied,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $icno = $this->icno($request);
        if ($icno === null) {
            return $this->redirect('/login');
        }

        if (!$this->access->isBpsmAdmin($icno)) {
            return $this->accessDenied->render(backUrl: '/menu');
        }

        $query = $request->getQueryParams();

        return $this->viewRenderer->render(__DIR__ . '/pending-list', [
            'list' => $this->service->adminPendingList(
                $this->queryString($query, 'carian'),
                $this->queryInt($query, 'page'),
            ),
        ]);
    }

    private function icno(ServerRequestInterface $request): ?string
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return null;
        }

        return $this->employees->icnoFromIdentity($legacySession['identityId']);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function queryString(array $query, string $key): string
    {
        return is_scalar($query[$key] ?? null) ? trim((string) $query[$key]) : '';
    }

    /**
     * @param array<string, mixed> $query
     */
    private function queryInt(array $query, string $key): int
    {
        $value = is_scalar($query[$key] ?? null) ? (int) $query[$key] : 1;

        return max(1, $value);
    }
}
