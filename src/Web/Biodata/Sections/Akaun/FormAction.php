<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Akaun;

use App\Shared\Biodata\Sections\AkaunCrudService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
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
        private AkaunCrudService $akaunCrudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $query = $request->getQueryParams();
        $id = (string) ($query['id'] ?? '');
        $targetIcno = $this->cleanQueryValue((string) ($query['icno'] ?? ''));
        $adminRoute = str_contains($request->getUri()->getPath(), '/admin-');
        if ($adminRoute && $targetIcno === '') {
            return $this->redirect('/biodata/userview?error=1');
        }

        $icno = $targetIcno !== ''
            ? $targetIcno
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $adminMode = $targetIcno !== '' || $adminRoute;
        $form = $icno === '' ? null : $this->akaunCrudService->form($icno, $id !== '' ? $id : null);
        if ($form === null) {
            return $this->redirect($adminMode ? '/biodata/adminview?id=' . rawurlencode($icno) . '&error=1' : '/biodata/userview?error=1');
        }

        if ($adminMode) {
            $form = $this->withAdminContext($form, $icno);
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->akaunCrudService->save(
                $icno,
                $body,
                $this->uploadedFiles($request),
                $id !== '' ? $id : null,
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                return $this->redirect($this->appendQuery((string) $form['backUrl'], [$id !== '' ? 'updated' : 'created' => '1']));
            }

            $form['values'] = $result['values'];
            return $this->render($form, $result['errors']);
        }

        return $this->render($form);
    }

    /**
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    private function withAdminContext(array $form, string $icno): array
    {
        $form['backUrl'] = '/biodata/akaun/admin-senarai-akaun?icno=' . rawurlencode($icno);
        $form['action'] = $this->appendQuery((string) ($form['action'] ?? ''), ['icno' => $icno]);

        return $form;
    }

    /**
     * @param array<string, string> $query
     */
    private function appendQuery(string $url, array $query): string
    {
        $queryString = http_build_query($query);
        if ($url === '' || $queryString === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $queryString;
    }

    private function cleanQueryValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return trim(strtok($value, '?&') ?: $value);
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
