<?php

declare(strict_types=1);

namespace App\Web\Biodata\Sections\Keahlian;

use App\Shared\BiodataProfileService;
use App\Shared\LegacySessionReader;
use App\Shared\Biodata\Sections\KeahlianCrudService;
use App\Web\Biodata\Sections\SectionTemplatePath;
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
        private KeahlianCrudService $keahlianCrudService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->redirect('/login');
        }

        $path = $request->getUri()->getPath();
        $template = 'form';
        $query = $request->getQueryParams();
        $targetIcno = $this->cleanQueryValue((string) ($query['icno'] ?? ''));
        $icno = $targetIcno !== ''
            ? $targetIcno
            : $this->profileService->icnoFromIdentity($legacySession['identityId']);
        $memberships = $icno === '' ? null : $this->keahlianCrudService->listForIcno($icno);
        $isUpdate = str_ends_with($path, '/update');
        $id = $isUpdate ? (string) ($query['id'] ?? '') : null;
        if ($memberships === null) {
            return $this->viewRenderer
                ->render(SectionTemplatePath::resolve($request, __DIR__, $template), [
                    'staff' => null,
                    'mode' => $isUpdate ? 'update' : 'create',
                    'options' => $this->keahlianCrudService->formOptions(),
                    'values' => $this->keahlianCrudService->defaultValues(),
                    'errors' => [],
                    'backUrl' => $this->backUrl($targetIcno),
                    'formAction' => $this->formAction($isUpdate, $id, $targetIcno),
                ])
                ->withStatus(404);
        }

        $values = $isUpdate
            ? ($id === null || $id === '' ? null : $this->keahlianCrudService->valuesForEdit($icno, $id))
            : $this->keahlianCrudService->defaultValues();
        if ($values === null) {
            return $this->viewRenderer
                ->render(SectionTemplatePath::resolve($request, __DIR__, $template), [
                    'staff' => $memberships,
                    'mode' => 'update',
                    'options' => $this->keahlianCrudService->formOptions(),
                    'values' => $this->keahlianCrudService->defaultValues(),
                    'errors' => ['general' => 'Rekod keahlian tidak dijumpai.'],
                    'backUrl' => $this->backUrl($targetIcno),
                    'formAction' => $this->formAction($isUpdate, $id, $targetIcno),
                ])
                ->withStatus(404);
        }

        $errors = [];

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->keahlianCrudService->saveForIcno(
                $icno,
                $body,
                $id,
                $this->uploadedFile($request, 'file'),
                $this->clientIp($request),
            );

            if ($result['success'] === true) {
                return $this->redirect($this->appendQuery($this->backUrl($targetIcno), [$isUpdate ? 'updated' : 'created' => '1']));
            }

            $values = $result['values'];
            $errors = $result['errors'];
        }

        return $this->viewRenderer->render(SectionTemplatePath::resolve($request, __DIR__, $template), [
            'staff' => $memberships,
            'mode' => $isUpdate ? 'update' : 'create',
            'options' => $this->keahlianCrudService->formOptions(),
            'values' => $values,
            'errors' => $errors,
            'backUrl' => $this->backUrl($targetIcno),
            'formAction' => $this->formAction($isUpdate, $id, $targetIcno),
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

    private function backUrl(string $icno): string
    {
        return $icno !== '' ? '/biodata/keahlian/adminview?icno=' . rawurlencode($icno) : '/biodata/keahlian/view';
    }

    private function formAction(bool $isUpdate, ?string $id, string $icno): string
    {
        $url = $isUpdate
            ? '/biodata/keahlian/update?id=' . rawurlencode((string) $id)
            : '/biodata/keahlian/tambahbadanprofesional';

        return $this->appendQuery($url, $icno !== '' ? ['icno' => $icno] : []);
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
}
