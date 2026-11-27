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
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class CounterUploadAction
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
        $adminIcno = $this->icno($request);
        if ($adminIcno === null) {
            return $this->redirect('/login');
        }

        if (!$this->access->isCounterAdmin($adminIcno)) {
            return $this->accessDenied->render(backUrl: '/menu');
        }

        $query = $request->getQueryParams();
        $staffIcno = is_scalar($query['id'] ?? null) ? trim((string) $query['id']) : '';
        $form = $staffIcno === '' ? null : $this->service->counterUploadForm($staffIcno);
        if ($form === null) {
            return $this->redirect('/gambar-profile/cari-kakitangan?notice=error&message=' . rawurlencode('Rekod staf tidak dijumpai.'));
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $result = $this->service->submitCounterUpload(
                $staffIcno,
                $adminIcno,
                $this->uploadedFile($request, 'imageFile'),
                $this->ipAddress($request),
            );

            if ($result['success'] === true) {
                $redirect = (string) ($result['redirect'] ?? '/gambar-profile/admin-senarai-penukaran');

                return $this->redirect($redirect . (str_contains($redirect, '?') ? '&' : '?') . http_build_query([
                    'notice' => 'success',
                    'message' => 'Gambar profil kakitangan berjaya dimuat naik.',
                ]));
            }

            return $this->render($form, $result['errors']);
        }

        return $this->render($form);
    }

    /**
     * @param array<string, mixed> $form
     * @param array<string, string> $errors
     */
    private function render(array $form, array $errors = []): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/counter-upload', [
            'form' => $form,
            'errors' => $errors,
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

    private function uploadedFile(ServerRequestInterface $request, string $name): ?UploadedFileInterface
    {
        $file = $request->getUploadedFiles()[$name] ?? null;

        return $file instanceof UploadedFileInterface ? $file : null;
    }

    private function ipAddress(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        foreach (['HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (is_scalar($server[$key] ?? null) && trim((string) $server[$key]) !== '') {
                $value = trim((string) $server[$key]);

                return str_contains($value, ',') ? trim(explode(',', $value)[0]) : $value;
            }
        }

        return '';
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
