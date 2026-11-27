<?php

declare(strict_types=1);

namespace App\Web\GambarProfile\Admin;

use App\Shared\EmployeeRepository;
use App\Shared\GambarProfile\ProfilePictureAdminAccessService;
use App\Shared\LegacySessionReader;
use App\Web\Shared\AccessDeniedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class AdminFormAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private ProfilePictureAdminAccessService $access,
        private AccessDeniedResponse $accessDenied,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $icno = $this->icno($request);
        if ($icno === null) {
            return $this->redirect('/login');
        }

        if (!$this->access->isCounterAdmin($icno)) {
            return $this->accessDenied->render(backUrl: '/menu');
        }

        $query = $request->getQueryParams();
        $isUpdateRoute = str_ends_with($request->getUri()->getPath(), '/gambar-profile/kemaskini-admin');
        $id = $isUpdateRoute && is_scalar($query['id'] ?? null) ? trim((string) $query['id']) : null;
        if ($isUpdateRoute && ($id === null || $id === '')) {
            return $this->redirect('/gambar-profile/senarai-admin?notice=error&message=' . rawurlencode('Rekod admin tidak dijumpai.'));
        }

        $form = $this->access->adminForm($icno, $id);
        if ($form === null) {
            return $this->redirect('/gambar-profile/senarai-admin?notice=error&message=' . rawurlencode('Rekod admin tidak dijumpai.'));
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = (array) ($request->getParsedBody() ?? []);
            $result = $this->access->saveAdmin($icno, $id, $body);
            if ($result['success'] === true) {
                return $this->redirect($result['redirect'] . '?' . http_build_query([
                    'notice' => 'success',
                    'message' => $result['message'],
                ]));
            }

            $form['values'] = [
                'icno' => $this->bodyString($body, 'icno'),
                'access_type' => $this->bodyString($body, 'access_type'),
                'is_active' => $this->bodyString($body, 'is_active'),
            ];

            return $this->render($form, $result['errors'], $result['message']);
        }

        return $this->render($form);
    }

    /**
     * @param array<string, mixed> $form
     * @param array<string, string> $errors
     */
    private function render(array $form, array $errors = [], string $message = ''): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/admin-form', [
            'form' => $form,
            'errors' => $errors,
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

    /**
     * @param array<string, mixed> $body
     */
    private function bodyString(array $body, string $key): string
    {
        return is_scalar($body[$key] ?? null) ? trim((string) $body[$key]) : '';
    }
}
