<?php

declare(strict_types=1);

namespace App\Web\GambarProfile\Staff;

use App\Shared\EmployeeRepository;
use App\Shared\GambarProfile\ProfilePictureService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class UploadAction
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

        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);
        $form = $icno === '' ? null : $this->service->uploadForm($icno);
        if ($form === null) {
            return $this->redirect('/biodata/userview?error=1');
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $result = $this->service->submitRequest($icno, $this->uploadedFile($request, 'imageFile'));
            if ($result['success'] === true) {
                return $this->redirect('/gambar-profile/semakan?created=1');
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
        return $this->viewRenderer->render(__DIR__ . '/upload', [
            'form' => $form,
            'errors' => $errors,
        ]);
    }

    private function uploadedFile(ServerRequestInterface $request, string $name): ?UploadedFileInterface
    {
        $file = $request->getUploadedFiles()[$name] ?? null;

        return $file instanceof UploadedFileInterface ? $file : null;
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
