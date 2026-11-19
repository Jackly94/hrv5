<?php

declare(strict_types=1);

namespace App\Web\Cuti\Individu;

use App\Shared\CutiApplicationService;
use App\Shared\CutiMenuService;
use App\Shared\EmployeeRepository;
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
        private EmployeeRepository $employees,
        private CutiMenuService $menu,
        private CutiApplicationService $applications,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);
        $path = $request->getUri()->getPath();
        $query = $request->getQueryParams();

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];

            $result = $this->applications->submit(
                $icno,
                $path,
                $query,
                $body,
                $this->uploadedFile($request, 'file'),
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                return $this->redirect('/cuti/individu/index?created=1');
            }

            $data = $this->applications->formData($icno, $path, $query, $result['values'], $result['errors']);
            if ($data === null) {
                return $this->redirect('/cuti/individu/pilih');
            }

            return $this->render($icno, $data);
        }

        $data = $this->applications->formData($icno, $path, $query);
        if ($data === null) {
            return $this->redirect('/cuti/individu/pilih');
        }

        return $this->render($icno, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $icno, array $data): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/form', [
            ...$data,
            'menuSections' => $this->menu->sectionsForStaff($icno),
        ]);
    }

    private function uploadedFile(ServerRequestInterface $request, string $field): ?UploadedFileInterface
    {
        $file = $request->getUploadedFiles()[$field] ?? null;

        return $file instanceof UploadedFileInterface ? $file : null;
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        foreach (['HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $value = $server[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return substr(trim(explode(',', $value)[0]), 0, 45);
            }
        }

        return 'UNKNOWN';
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
