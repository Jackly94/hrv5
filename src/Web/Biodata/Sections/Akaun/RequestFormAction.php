<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Akaun;

use App\Shared\Biodata\Sections\AkaunRequestService;
use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class RequestFormAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private AkaunRequestService $requestService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $form = $icno === '' ? null : $this->requestService->form($icno);
        if ($form === null) {
            return $this->redirect('/biodata/userview?error=1');
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->requestService->save($icno, $body, $this->uploadedFiles($request));

            if ($result['success'] === true) {
                return $this->redirect('/biodata/akaun/permohonan-penukaran-akaun-gaji?created=1');
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

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
