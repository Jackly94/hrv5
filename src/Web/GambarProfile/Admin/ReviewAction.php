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

final readonly class ReviewAction
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
        $id = is_scalar($query['id'] ?? null) ? trim((string) $query['id']) : '';

        if (strtoupper($request->getMethod()) === 'POST') {
            $result = $this->service->adminDecision($id, $icno, (array) ($request->getParsedBody() ?? []));
            $redirect = $result['redirect'] . (str_contains($result['redirect'], '?') ? '&' : '?') . http_build_query([
                'notice' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
            ]);

            return $this->redirect($redirect);
        }

        $notice = is_scalar($query['notice'] ?? null) ? (string) $query['notice'] : '';
        $message = is_scalar($query['message'] ?? null) ? (string) $query['message'] : '';

        return $this->viewRenderer->render(__DIR__ . '/review', [
            'review' => $this->service->adminReview($id),
            'notice' => in_array($notice, ['success', 'error'], true) ? $notice : '',
            'message' => $message,
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
}
