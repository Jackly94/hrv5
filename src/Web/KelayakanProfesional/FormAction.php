<?php

declare(strict_types=1);

namespace App\Web\KelayakanProfesional;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\ProfessionalCertificateService;
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
        private ProfessionalCertificateService $certificateService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $staff = $icno === '' ? null : $this->certificateService->staffForIcno($icno);
        if ($staff === null) {
            return $this->render($request, null, $this->certificateService->defaultValues($icno), [], 404);
        }

        $path = $request->getUri()->getPath();
        $isUpdate = str_ends_with($path, '/update-cc');
        $query = $request->getQueryParams();
        $id = $isUpdate ? (string) ($query['id'] ?? '') : null;
        $values = $isUpdate
            ? ($id === null || $id === '' ? null : $this->certificateService->valuesForEdit($icno, $id))
            : $this->certificateService->defaultValues($icno);

        if ($values === null) {
            return $this->render(
                $request,
                $staff,
                $this->certificateService->defaultValues($icno),
                ['general' => 'Rekod kelayakan profesional tidak dijumpai.'],
                404,
            );
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->certificateService->save(
                $icno,
                $body,
                $id,
                $this->uploadedFile($request, 'file'),
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                return $this->redirect('/kelayakan-profesional/view?' . ($isUpdate ? 'updated=1' : 'created=1'));
            }

            return $this->render($request, $staff, $result['values'], $result['errors']);
        }

        return $this->render($request, $staff, $values);
    }

    /**
     * @param array{name: string}|null $staff
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function render(
        ServerRequestInterface $request,
        ?array $staff,
        array $values,
        array $errors = [],
        int $status = 200,
    ): ResponseInterface {
        $isUpdate = str_ends_with($request->getUri()->getPath(), '/update-cc');

        return $this->viewRenderer
            ->render(__DIR__ . '/form', [
                'staff' => $staff,
                'mode' => $isUpdate ? 'update' : 'create',
                'options' => $this->certificateService->formOptions(),
                'values' => $values,
                'errors' => $errors,
            ])
            ->withStatus($status);
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
                return trim(explode(',', $value)[0]);
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
