<?php

declare(strict_types=1);

namespace App\Web\BadanProfesional;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\ProfessionalMembershipService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class AddAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private BiodataProfileService $profileService,
        private ProfessionalMembershipService $membershipService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $icno = $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $memberships = $icno === '' ? null : $this->membershipService->listForIcno($icno);
        $path = $request->getUri()->getPath();
        $query = $request->getQueryParams();
        $isUpdate = str_ends_with($path, '/update');
        $id = $isUpdate ? (string) ($query['id'] ?? '') : null;
        if ($memberships === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/add', [
                    'staff' => null,
                    'mode' => $isUpdate ? 'update' : 'create',
                    'options' => $this->membershipService->formOptions(),
                    'values' => $this->membershipService->defaultValues(),
                    'errors' => [],
                ])
                ->withStatus(404);
        }

        $values = $isUpdate
            ? ($id === null || $id === '' ? null : $this->membershipService->valuesForEdit($icno, $id))
            : $this->membershipService->defaultValues();
        if ($values === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/add', [
                    'staff' => $memberships,
                    'mode' => 'update',
                    'options' => $this->membershipService->formOptions(),
                    'values' => $this->membershipService->defaultValues(),
                    'errors' => ['general' => 'Rekod keahlian tidak dijumpai.'],
                ])
                ->withStatus(404);
        }

        $errors = [];

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->membershipService->saveForIcno(
                $icno,
                $body,
                $id,
                $this->uploadedFile($request, 'file'),
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                return $this->redirect('/badan-profesional/view?' . ($isUpdate ? 'updated=1' : 'created=1'));
            }

            $values = $result['values'];
            $errors = $result['errors'];
        }

        return $this->viewRenderer->render(__DIR__ . '/add', [
            'staff' => $memberships,
            'mode' => $isUpdate ? 'update' : 'create',
            'options' => $this->membershipService->formOptions(),
            'values' => $values,
            'errors' => $errors,
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
