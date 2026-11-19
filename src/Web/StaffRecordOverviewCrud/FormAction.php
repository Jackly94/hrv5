<?php

declare(strict_types=1);

namespace App\Web\StaffRecordOverviewCrud;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\StaffRecordOverviewCrudService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class FormAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private StaffRecordOverviewCrudService $crudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $path = $request->getUri()->getPath();
        $key = $this->crudService->keyFromPath($path);
        if ($key === null) {
            return $this->redirect('/biodata/userview?error=1');
        }

        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $form = $this->crudService->form($key, $icno, $id !== '' ? $id : null);
        if ($form === null) {
            return $this->redirect('/biodata/userview?error=1');
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->crudService->save(
                $key,
                $icno,
                $body,
                $this->uploadedFiles($request),
                $id !== '' ? $id : null,
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                $config = $this->crudService->config($key);
                return $this->redirect(($config['backUrl'] ?? '/biodata/userview') . '?' . ($id !== '' ? 'updated=1' : 'created=1'));
            }

            $form['values'] = $result['values'];
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
        return $this->viewRenderer->render(__DIR__ . '/form', [
            'form' => $form,
            'errors' => $errors,
        ]);
    }

    /**
     * @return array<string, UploadedFileInterface|null>
     */
    private function uploadedFiles(ServerRequestInterface $request): array
    {
        $files = [];
        foreach ($request->getUploadedFiles() as $name => $file) {
            $files[$name] = $file instanceof UploadedFileInterface ? $file : null;
        }

        return $files;
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        foreach (['HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $value = $server[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim(explode(',', $value)[0]);
            }
        }

        return 'UNKNOWN';
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
